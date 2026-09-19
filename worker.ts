import { getContainer, ContainerProxy } from "@cloudflare/containers";

export { ContainerProxy };
export { AtrelladoContainer } from "./do";

export default {
	async fetch(request: Request, env: Env): Promise<Response> {
		const url = new URL(request.url);

		// Uploaded objects stream straight from R2, no container boot needed.
		if (url.pathname.startsWith("/storage/") && (request.method === "GET" || request.method === "HEAD")) {
			const key = decodeURIComponent(url.pathname.slice("/storage/".length));
			const headers = new Headers();
			const object =
				request.method === "HEAD"
					? await env.FILES.head(key)
					: await env.FILES.get(key, { onlyIf: request.headers });
			if (!object) return new Response("Not found", { status: 404 });
			object.writeHttpMetadata(headers);
			headers.set("etag", object.httpEtag);
			headers.set("Cache-Control", "public, max-age=300");
			if (!("body" in object) || !object.body) return new Response(null, { status: 304, headers });

			return new Response(request.method === "HEAD" ? null : object.body, { headers });
		}

		return fetchWithBootHold(request, env);
	},
} satisfies ExportedHandler<Env>;

// A cold container answers 503 + Retry-After until its entrypoint finishes
// migrating; hold the request instead of surfacing an error page. The app
// owns the pacing via Retry-After, the worker owns the deadline — a 503
// without the header is not the boot gate and passes through untouched.
async function fetchWithBootHold(request: Request, env: Env): Promise<Response> {
	const container = getContainer(env.CONTAINER, "atrellado");
	const deadline = Date.now() + 25_000;

	let response = await container.fetch(request.clone());
	while (response.status === 503 && Date.now() < deadline) {
		const retryAfter = Number(response.headers.get("retry-after"));
		if (!Number.isFinite(retryAfter)) break;
		await new Promise((resolve) =>
			setTimeout(resolve, Math.min(Math.max(retryAfter * 1000, 500), 5_000)),
		);
		response = await container.fetch(request.clone());
	}

	return response;
}

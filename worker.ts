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

		return getContainer(env.CONTAINER, "atrellado").fetch(request);
	},
} satisfies ExportedHandler<Env>;

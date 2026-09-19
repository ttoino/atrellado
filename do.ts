import { Container } from "@cloudflare/containers";
import { env as workerEnv } from "cloudflare:workers";
import { EmailMessage } from "cloudflare:email";
import { createMimeMessage } from "mimetext";

export class AtrelladoContainer extends Container<Env> {
	defaultPort = 8080;
	sleepAfter = "10m";
	pingEndpoint = "/ping";

	override onStop(stop: { exitCode: number; reason: string }): void {
		console.log(`container stopped: code=${stop.exitCode} reason=${stop.reason}`);
	}

	override onError(error: unknown): void {
		console.log(`container error: ${error}`);
	}

	// Injected into the container at boot; secrets come from worker secrets.
	envVars = {
		APP_KEY: workerEnv.APP_KEY,
		APP_ENV: "production",
		// Overridable via .dev.vars for local debugging.
		APP_DEBUG: (workerEnv as unknown as { APP_DEBUG?: string }).APP_DEBUG ?? "false",
		APP_URL: "https://atrellado.toino.pt",
		DB_CONNECTION: "d1",
		DB_D1_ENDPOINT: "http://d1.app",
		FILESYSTEM_DRIVER: "r2",
		R2_ENDPOINT: "http://r2.app",
		MAIL_MAILER: "http-mail",
		MAIL_ENDPOINT: "http://mail.app",
		MAIL_FROM_ADDRESS: "noreply@atrellado.toino.pt",
		MAIL_FROM_NAME: "Atrellado",
		CACHE_DRIVER: "database",
		SESSION_DRIVER: "cookie",
		SESSION_SECURE_COOKIE: "true",
		QUEUE_CONNECTION: "sync",
		LOG_CHANNEL: "stderr",
	};
}

// Magic hosts for the container's egress: plain HTTP on the same machine,
// resolved against this worker's bindings. Mirrors the protocols in
// App\Support\D1\D1HttpClient, App\Support\R2\HttpR2Adapter and
// App\Support\Mailer\HttpMailTransport.
AtrelladoContainer.outboundByHost = {
	// Debug: the container posts boot output here; shows up in the tail.
	"log.app": async (request) => {
		console.log("container boot:", await request.text());
		return new Response("ok");
	},

	"d1.app": async (request, env) => {
		try {
			const url = new URL(request.url);
			const body = (await request.json()) as { sql: string; params?: unknown[] };
			if (url.pathname === "/exec") {
				return Response.json(await env.DB.exec(body.sql));
			}
			const statement = env.DB.prepare(body.sql);
			return Response.json(await (body.params?.length ? statement.bind(...body.params) : statement).all());
		} catch (error) {
			return Response.json({ error: String(error) }, { status: 500 });
		}
	},

	"r2.app": async (request, env) => {
		const url = new URL(request.url);
		if (url.searchParams.has("list")) {
			const page = await env.FILES.list({
				prefix: url.searchParams.get("prefix") ?? undefined,
				limit: Number(url.searchParams.get("limit") ?? 1000),
				cursor: url.searchParams.get("cursor") ?? undefined,
			});
			return Response.json({
				objects: page.objects.map((object) => ({ key: object.key, size: object.size })),
				truncated: page.truncated,
				cursor: page.truncated ? page.cursor : null,
			});
		}

		const key = decodeURIComponent(url.pathname.slice(1));
		const headers = new Headers();
		switch (request.method) {
			case "GET": {
				const object = await env.FILES.get(key);
				if (!object) return new Response("Not found", { status: 404 });
				object.writeHttpMetadata(headers);
				return new Response(object.body, { headers });
			}
			case "HEAD": {
				const object = await env.FILES.head(key);
				if (!object) return new Response("Not found", { status: 404 });
				object.writeHttpMetadata(headers);
				headers.set("Content-Length", String(object.size));
				headers.set("Last-Modified", object.uploaded.toUTCString());
				return new Response(null, { headers });
			}
			case "PUT":
				await env.FILES.put(key, request.body, {
					httpMetadata: { contentType: request.headers.get("Content-Type") ?? undefined },
				});
				return new Response("ok");
			case "DELETE": {
				const batch = (await request.json().catch(() => null)) as { keys?: string[] } | null;
				await env.FILES.delete(batch?.keys ?? key);
				return new Response("ok");
			}
			default:
				return new Response("Method not allowed", { status: 405 });
		}
	},

	"mail.app": async (request, env) => {
		try {
			const { from, to, subject, html, text } = (await request.json()) as {
				from: string;
				to: string[];
				subject: string;
				html?: string;
				text?: string;
			};
			// EmailMessage takes one recipient; build a message per address.
			for (const addr of to) {
				const message = createMimeMessage();
				message.setSender({ addr: from });
				message.setRecipient({ addr });
				message.setSubject(subject ?? "");
				if (text) message.addMessage({ contentType: "text/plain", data: text });
				if (html) message.addMessage({ contentType: "text/html", data: html });
				await env.EMAIL.send(new EmailMessage(from, addr, message.asRaw()));
			}
			return new Response("sent");
		} catch (error) {
			return Response.json({ error: String(error) }, { status: 500 });
		}
	},
};

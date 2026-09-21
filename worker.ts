import { Container, getContainer } from "@cloudflare/containers";
import { env as workerEnv } from "cloudflare:workers";
import {
    ContainerProxy,
    d1,
    kv,
    log,
    mail,
    PhpContainer,
    phpOutbound,
    phpWorker,
    queue,
    r2,
} from "workers-php";

const reverbApp = {
    REVERB_APP_ID: "atrellado",
    REVERB_APP_KEY: "2e1a6646d9367077cfa5",
    REVERB_APP_SECRET: workerEnv.REVERB_APP_SECRET,
};

export class AtrelladoContainer extends PhpContainer {
    envVars = {
        APP_ENV: "production",
        APP_KEY: workerEnv.APP_KEY,
        APP_URL: "https://atrellado.toino.pt",
        BROADCAST_CONNECTION: "reverb",
        CACHE_DRIVER: "kv",
        DB_CONNECTION: "d1",
        DB_D1_ENDPOINT: "http://example.com/DB",
        FILESYSTEM_DRIVER: "r2",
        KV_ENDPOINT: "http://example.com/KV",
        LOG_CHANNEL: "stderr",
        MAIL_ENDPOINT: "http://example.com/EMAIL",
        MAIL_FROM_ADDRESS: "noreply@atrellado.toino.pt",
        MAIL_FROM_NAME: "Atrellado",
        MAIL_MAILER: "http-mail",
        PULSE_DB_CONNECTION: "sqlite",
        QUEUE_CONNECTION: "cfqueue",
        QUEUE_ENDPOINT: "http://example.com/QUEUE",
        R2_ENDPOINT: "http://example.com/FILES",
        ...reverbApp,
        REVERB_HOST: "atrellado.toino.pt",
        REVERB_PORT: "443",
        REVERB_SCHEME: "https",
        SESSION_DRIVER: "cookie",
        SESSION_SECURE_COOKIE: "true",
        WORKERS_PHP: "true",
    };
    pingEndpoint = "/ping.php";
    sleepAfter = "1h";
}

AtrelladoContainer.outboundByHost = phpOutbound(
    d1("DB"),
    r2("FILES"),
    kv("KV"),
    queue("QUEUE"),
    mail("EMAIL"),
    log(),
);

export class ReverbContainer extends Container {
    defaultPort = 8080;
    entrypoint = [
        "php",
        "artisan",
        "reverb:start",
        "--host=0.0.0.0",
        "--port=8080",
    ];
    envVars = {
        APP_KEY: workerEnv.APP_KEY,
        ...reverbApp,
    };
    sleepAfter = "1h";
}

export { ContainerProxy };

const handler = phpWorker({
    consume: true,
    container: "CONTAINER",
    name: "atrellado",
    schedule: true,
    storage: { bucket: "FILES", prefix: "/storage/" },
});

export default {
    ...handler,
    fetch: (request, env, ctx) => {
        const path = new URL(request.url).pathname;
        if (
            path.startsWith("/apps/") ||
            (path.startsWith("/app/") &&
                request.headers.get("upgrade") === "websocket")
        ) {
            return getContainer(env.REVERB_CONTAINER, "reverb").fetch(request);
        }
        return (
            handler.fetch?.(request, env, ctx) ??
            new Response(null, { status: 404 })
        );
    },
} satisfies ExportedHandler<Env>;

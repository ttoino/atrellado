import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo?: Echo<"reverb"> | null;
        // Injected per-request by layouts/bare from the cached runtime
        // config; takes precedence over the build-time VITE_REVERB_* vars.
        reverbConfig?: {
            host?: string;
            key?: string;
            port?: number | string;
            scheme?: string;
        };
    }
}

// Echo 2.x resolves the pusher client from the global scope.
window.Pusher = Pusher;

// Payloads mirror the broadcastWith() arrays of the App\Events classes;
// full models are fetched via the API so broadcasts stay small.
export interface TaskCommentCreatedPayload {
    id: number;
    project_id: number;
    task_id: number;
}

export interface ThreadCommentCreatedPayload {
    id: number;
    project_id: number;
    thread_id: number;
}

export interface ThreadCreatedPayload {
    id: number;
    project_id: number;
}

// Stays null when Reverb is not configured or fails to start, so pages work
// without a websocket server.
let echo: Echo<"reverb"> | null = null;

const runtime = window.reverbConfig;

const key: string | undefined =
    runtime?.key ?? import.meta.env.VITE_REVERB_APP_KEY;
const host: string | undefined =
    runtime?.host ?? import.meta.env.VITE_REVERB_HOST;
const port: number | undefined =
    Number(runtime?.port ?? import.meta.env.VITE_REVERB_PORT) || undefined;
const scheme: string =
    runtime?.scheme ?? import.meta.env.VITE_REVERB_SCHEME ?? "https";

if (key) {
    try {
        echo = new Echo<"reverb">({
            broadcaster: "reverb",
            enabledTransports: ["ws", "wss"],
            forceTLS: scheme === "https",
            key,
            wsHost: host,
            wsPort: port,
            wssPort: port,
        });

        window.Echo = echo;
    } catch (error) {
        console.error("Failed to start Echo:", error);
    }
}

export const projectChannel = (projectId: string) =>
    echo?.private(`project.${projectId}`);

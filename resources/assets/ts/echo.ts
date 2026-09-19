import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
    interface Window {
        Pusher: typeof Pusher;
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

const key: string | undefined = import.meta.env.VITE_REVERB_APP_KEY;

if (key) {
    try {
        echo = new Echo<"reverb">({
            broadcaster: "reverb",
            enabledTransports: ["ws", "wss"],
            forceTLS:
                (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
            key,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        });
    } catch (error) {
        console.error("Failed to start Echo:", error);
    }
}

export const projectChannel = (projectId: string) =>
    echo?.private(`project.${projectId}`);

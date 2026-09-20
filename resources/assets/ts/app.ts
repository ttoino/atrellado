/**
 * First, we will load all of this project's Javascript utilities and other
 * dependencies. Then, we will be ready to develop a robust and powerful
 * application frontend using useful Laravel and JavaScript libraries.
 */

import "bootstrap";

// Enhancements
import "./enhancements/autoresize";
import "./enhancements/form";
import "./enhancements/imageinput";
import "./enhancements/passwordinput";
import "./enhancements/singleline";
import "./enhancements/tooltip";
// Echo + Reverb: boots the websocket client used by the pages' live updates.
import "./echo";
// Board drag-and-drop bridge (guards on [data-board] presence).
import "./board-dnd";
import { renderToast } from "./toast";

declare global {
    interface Window {
        Livewire?: {
            dispatch: (event: string, params?: Record<string, unknown>) => void;
            hook: (
                name: string,
                cb: (payload: { el: unknown }) => void,
            ) => void;
            on: (event: string, cb: (event: unknown) => void) => void;
        };
    }
}

// Livewire components surface action feedback as toasts.
document.addEventListener("livewire:init", () => {
    window.Livewire?.on("toast", (event) => {
        const text =
            typeof event === "string"
                ? event
                : ((event as { text?: string }).text ?? "");
        renderToast?.({ text });
    });
});

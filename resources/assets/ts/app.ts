/**
 * First, we will load all of this project's Javascript utilities and other
 * dependencies. Then, we will be ready to develop a robust and powerful
 * application frontend using useful Laravel and JavaScript libraries.
 */

import "bootstrap";

if (window.location.pathname.match(/project\/\d+\/(board|task\/\d+)/))
    import("./pages/board");


// Enhancements
import "./enhancements/autoresize";
import "./enhancements/form";
import "./enhancements/imageinput";
import "./enhancements/passwordinput";
import "./enhancements/singleline";
import "./enhancements/tooltip";
// Echo + Reverb: boots the websocket client used by the pages' live updates.
import "./echo";
import { renderToast } from "./toast";

declare global {
    interface Window {
        Livewire?: {
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

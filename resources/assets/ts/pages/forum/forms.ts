import { editThread, newThread } from "../../api/thread";
import { editThreadComment, newThreadComment } from "../../api/thread_comment";
import { registerEnhancement } from "../../enhancements";
import { ajaxForm } from "../../forms";
import { Route } from "../../navigation";
import { Thread } from "../../types/thread";
import { projectId } from "../project";
import { showThreadOffcanvas } from "./navigation";
import {
    appendThreadComment,
    appendThreadListItem,
    renderThread,
    renderThreadComment,
    renderThreadListItem,
} from "./render";

registerEnhancement<HTMLFormElement>({
    onattach: (form) => {
        ajaxForm(
            newThread,
            form,
            { project_id: parseInt(projectId) },
            (thread) => {
                const state: Route<Thread> = {
                    data: thread,
                    name: "project.thread",
                    state: "ok",
                };

                history.pushState(
                    state,
                    "",
                    `/project/${projectId}/thread/${thread.id}`,
                );

                showThreadOffcanvas();
                renderThread?.(thread);
                appendThreadListItem?.(thread);
            },
            () => {},
        );
    },
    selector: "#new-thread-offcanvas > form",
});

registerEnhancement<HTMLFormElement>({
    onattach: (form) => {
        ajaxForm(
            newThreadComment,
            form,
            {},
            (threadComment) => {
                appendThreadComment?.(threadComment);
            },
            () => {},
        );
    },
    selector: "form#new-comment-form",
});

// EDIT THREAD
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            editThread,
            form,
            {},
            (thread) => {
                renderThread?.(thread);
                renderThreadListItem(thread);
                document.querySelector("#thread")?.classList.remove("editing");
            },
            () => {},
        ),
    selector: "form#edit-thread-form",
});

// EDIT THREAD COMMENT
registerEnhancement<HTMLFormElement>({
    onattach: (form) =>
        ajaxForm(
            editThreadComment,
            form,
            {},
            (threadComment) => {
                renderThreadComment?.(threadComment);
                document
                    .querySelector(
                        `.thread-comment[data-thread-comment-id="${threadComment.id}"]`,
                    )
                    ?.classList.remove("editing");
            },
            () => {},
        ),
    selector: "form.edit-thread-comment-form",
});

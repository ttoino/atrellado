import { tryRequest } from "../../api";
import { getThread } from "../../api/thread";
import { getThreadComment } from "../../api/thread_comment";
import {
    projectChannel,
    ThreadCommentCreatedPayload,
    ThreadCreatedPayload,
} from "../../echo";
import { projectId } from "../project";
import { appendThreadComment, appendThreadListItem } from "./render";

// The posting client appends its own threads/comments on POST success, so
// incoming events are de-duplicated by id before fetching and appending.
const channel = projectChannel(projectId);

channel?.listen(".thread.created", async (payload: ThreadCreatedPayload) => {
    if (document.querySelector(`.thread[data-thread-id="${payload.id}"]`))
        return;

    const thread = await tryRequest(getThread, undefined, String(payload.id));

    if (thread) appendThreadListItem?.(thread);
});

channel?.listen(
    ".thread-comment.created",
    async (payload: ThreadCommentCreatedPayload) => {
        const openThread = document.querySelector<HTMLElement>(
            "#thread[data-thread-id]",
        );

        if (openThread?.dataset.threadId !== String(payload.thread_id)) return;
        if (
            document.querySelector(
                `.thread-comment[data-thread-comment-id="${payload.id}"]`,
            )
        )
            return;

        const comment = await tryRequest(
            getThreadComment,
            undefined,
            String(payload.id),
        );

        if (comment) appendThreadComment?.(comment);
    },
);

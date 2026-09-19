import { apiFetch } from ".";
import { Paginator } from "../types/misc";
import { ThreadComment } from "../types/thread_comment";

export const getThreadComments = (threadId: string, cursor = "") =>
    apiFetch<Paginator<ThreadComment>>(`/api/thread-comment/`, "GET", {
        cursor,
        thread_id: threadId,
    });

export const getThreadComment = (threadCommentId: string) =>
    apiFetch<ThreadComment>(`/api/thread-comment/${threadCommentId}`);

export const newThreadComment = (threadComment: ThreadComment) =>
    apiFetch<ThreadComment>("/api/thread-comment", "POST", threadComment);

export const editThreadComment = (threadComment: ThreadComment) =>
    apiFetch<ThreadComment>(
        `/api/thread-comment/${threadComment.id}`,
        "PUT",
        threadComment,
    );

export const deleteThreadComment = (threadCommentId: string) =>
    apiFetch<ThreadComment>(`/api/thread-comment/${threadCommentId}`, "DELETE");

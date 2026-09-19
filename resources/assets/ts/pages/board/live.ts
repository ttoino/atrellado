import { tryRequest } from "../../api";
import { getTaskComment } from "../../api/task_comment";
import { projectChannel, TaskCommentCreatedPayload } from "../../echo";
import { projectId } from "../project";
import { appendTaskComment } from "./render";

// Only the open task's comments are appended. The posting client appends its
// own comment on POST success, so incoming events are de-duplicated by id.
projectChannel(projectId)?.listen(
    ".task-comment.created",
    async (payload: TaskCommentCreatedPayload) => {
        const openTask = document.querySelector<HTMLElement>(
            "#task[data-task-id]",
        );

        if (openTask?.dataset.taskId !== String(payload.task_id)) return;
        if (
            document.querySelector(
                `.task-comment[data-task-comment-id="${payload.id}"]`,
            )
        )
            return;

        const comment = await tryRequest(
            getTaskComment,
            undefined,
            String(payload.id),
        );

        if (comment) appendTaskComment?.(comment);
    },
);

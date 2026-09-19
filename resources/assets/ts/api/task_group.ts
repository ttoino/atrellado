import { apiFetch } from ".";
import { TaskGroup } from "../types/task_group";

export const repositionTaskGroup = (
    taskGroupId: string,
    position: null | string,
) =>
    apiFetch<TaskGroup>(`/api/task-group/${taskGroupId}/reposition`, "POST", {
        position,
    });

export const newTaskGroup = (group: TaskGroup) =>
    apiFetch<TaskGroup>("/api/task-group", "POST", group);

export const editTaskGroup = (group: TaskGroup) =>
    apiFetch<TaskGroup>(`/api/task-group/${group.id}`, "PUT", group);

export const deleteTaskGroup = (taskGroupId: string) =>
    apiFetch<TaskGroup>(`/api/task-group/${taskGroupId}`, "DELETE");

import { apiFetch } from ".";
import { Tag } from "../types/tag";

export const getTag = (tagId: string) => apiFetch<Tag>(`/api/tag/${tagId}`);

export const newTag = (tag: Tag) => apiFetch<Tag>("/api/tag", "POST", tag);

export const editTag = (tag: Tag) =>
    apiFetch<Tag>(`/api/tag/${tag.id}`, "PUT", tag);

export const deleteTag = (tagId: string) =>
    apiFetch<Tag>(`/api/tag/${tagId}`, "DELETE");

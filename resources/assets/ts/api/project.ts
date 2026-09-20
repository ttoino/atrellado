import { apiFetch } from ".";
import { Project } from "../types/project";

export const toggleFavorite = (projectId: string) =>
    apiFetch<{ isFavorite: boolean }>(
        `/api/project/${projectId}/favorite/toggle`,
        "POST",
    );

export const deleteProject = (projectId: string) =>
    apiFetch<Project>(`/api/project/${projectId}`, "DELETE");

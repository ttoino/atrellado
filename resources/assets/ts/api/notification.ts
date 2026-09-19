import { apiFetch } from ".";
import { Notification } from "../types/notification";

export const getNotification = (notificationId: string) =>
    apiFetch<Notification<any>>(`/api/notifications/${notificationId}`);

export const markNotificationAsRead = (notificationId: string) =>
    apiFetch<Notification<any>>(
        `/api/notifications/${notificationId}/read`,
        "PUT",
    );

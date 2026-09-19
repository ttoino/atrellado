import { apiFetch } from ".";
import { Notification, NotificationTypeMap } from "../types/notification";

export const getNotification = (notificationId: string) =>
    apiFetch<Notification<keyof NotificationTypeMap>>(
        `/api/notifications/${notificationId}`,
    );

export const markNotificationAsRead = (notificationId: string) =>
    apiFetch<Notification<keyof NotificationTypeMap>>(
        `/api/notifications/${notificationId}/read`,
        "PUT",
    );

import { Datetime } from "./misc";

export interface Notification<T extends keyof NotificationTypeMap> {
    creation_date: Datetime;
    id: number;
    json: NotificationTypeMap[T];
    notifiable_id: number;
    read_date: Datetime;
    type: T;
}

interface NotificationTypeMap {
    "App\\Notifications\\ProjectInvite": {
        url: string;
    };
}

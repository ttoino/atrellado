import { Datetime } from "./misc";

export interface Notification<T extends keyof NotificationTypeMap> {
    created_at: Datetime;
    data: NotificationTypeMap[T];
    id: string;
    read_at: Datetime | null;
    type: T;
}

export interface NotificationTypeMap {
    "App\\Notifications\\ProjectArchived": ProjectNotification;
    "App\\Notifications\\ProjectDeleted": {
        project_name: string;
    };
    "App\\Notifications\\ProjectInvite": ProjectNotification;
    "App\\Notifications\\ProjectRemoved": ProjectNotification;
    "App\\Notifications\\TaskAssigned": TaskNotification;
    "App\\Notifications\\TaskCommented": TaskCommentedNotification;
    "App\\Notifications\\TaskCompleted": TaskNotification;
    "App\\Notifications\\ThreadCommented": ThreadCommentedNotification;
    "App\\Notifications\\ThreadNew": ThreadNotification;
}

interface ProjectNotification {
    project_id: number;
    project_name: string;
    url: string;
}

interface TaskCommentedNotification extends TaskNotification {
    author_name: string;
    comment_id: number;
}

interface TaskNotification {
    project_id: number;
    project_name: string;
    task_id: number;
    task_name: string;
    url: string;
}

interface ThreadCommentedNotification extends ThreadNotification {
    comment_id: number;
}

interface ThreadNotification {
    author_name: string;
    project_id: number;
    project_name: string;
    thread_id: number;
    thread_title: string;
    url: string;
}

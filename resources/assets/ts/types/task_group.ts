import { Datetime, Markdown } from "./misc";
import { Task } from "./task";

export interface TaskGroup {
    creation_date: Datetime;
    description: Markdown;
    id: number;
    name: string;
    position: number;
    project_id: number;

    tasks?: Array<Task>;
}

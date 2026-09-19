export interface Datetime {
    date: string;
    datetime: string;
    diff: string;
    iso: string;
    long_diff: string;
    time: string;
}

export interface Markdown {
    formatted: string;
    raw: string;
}

export interface Paginator<T> {
    data: Array<T>;
    links: {
        first: null | string;
        last: null | string;
        next: null | string;
        prev: null | string;
    };
    meta: {
        next_cursor: null | string;
        path: string;
        per_page: number;
        prev_cursor: null | string;
    };
}

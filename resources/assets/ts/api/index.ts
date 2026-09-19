import { renderToast } from "../toast";

const token =
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? "";

export interface APIError {
    message: string;
}

export type APIMethod = "DELETE" | "GET" | "POST" | "PUT";

export type EnhancedResponse<T> = ErrorResponse | SuccessfulResponse<T>;

interface ErrorResponse extends Response {
    json(): Promise<APIError>;
    ok: false;
}

interface SuccessfulResponse<T> extends Response {
    json(): Promise<T>;
    ok: true;
}

export const apiFetch = <T>(
    url: RequestInfo,
    method: APIMethod = "GET",
    body?: unknown,
    options?: RequestInit,
): Promise<EnhancedResponse<T>> => {
    console.log(
        `Making ${method} request to ${url} with options ${options} and body ${JSON.stringify(
            body,
        )}`,
    );

    if (method === "GET") {
        url += "?" + new URLSearchParams(body as Record<string, string>);
        body = undefined;
    }

    return fetch(url, {
        ...options,
        body: JSON.stringify(body),
        headers: {
            ...options?.headers,
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": token,
        },
        method,
    });
};

export const tryRequest = async <K, Params extends unknown[]>(
    fn: (...params: Params) => ReturnType<typeof apiFetch<K>>,
    error: string = "Request failed, are you online?",
    ...params: Params
): Promise<K | null> => {
    try {
        const response = await fn(...params);

        if (!response.ok) {
            const message = (await response.json()).message;

            renderToast?.({ text: message });
            return null;
        }

        return await response.json();
    } catch (e) {
        console.error(e);
        renderToast?.({ text: error });
        return null;
    }
};

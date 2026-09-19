import { APIError, apiFetch } from "./api";

export interface Route<Data> {
    data: Data;
    name: string;
    state: "loading" | "not ok" | "ok";
}

export const navigation = (
    name: string,
    newUrl: string,
    onNavigate: () => unknown,
) => {
    window.addEventListener("popstate", (e) => {
        if (e.state.name != name) return;
        onNavigate();
    });

    return () => {
        if (window.location.toString() == newUrl) return;

        const state: Route<null> = {
            data: null,
            name,
            state: "ok",
        };
        history.pushState(state, "", newUrl);
        onNavigate();
    };
};

export const ajaxNavigation = <Data, Params extends unknown[]>(
    name: string,
    fn: (...params: Params) => ReturnType<typeof apiFetch<Data>>,
    ok: (response: Data) => unknown,
    notOk: (response: unknown) => unknown,
    loading: () => unknown,
) => {
    window.addEventListener("popstate", (e) => {
        if (e.state.name != name) return;
        if (e.state.state == "ok") ok(e.state.data);
        else if (e.state.state == "not ok") notOk(e.state.data);
        else if (e.state.state == "loading") loading();
    });

    return (newUrl: string, ...params: Params) => {
        fn(...params)
            .then(async (r) => {
                if (r.ok) {
                    const state: Route<Data> = {
                        data: await r.json(),
                        name,
                        state: "ok",
                    };

                    if (history.state.name == name)
                        history.replaceState(state, "", newUrl);

                    ok(state.data);
                } else {
                    const state: Route<APIError> = {
                        data: await r.json(),
                        name,
                        state: "ok",
                    };

                    if (history.state.name == name)
                        history.replaceState(state, "", newUrl);

                    notOk(state.data);
                }
            })
            .catch(async (r) => {
                const state: Route<unknown> = {
                    data: await r.json(),
                    name,
                    state: "not ok",
                };

                if (history.state.name == name)
                    history.replaceState(state, "", newUrl);

                notOk(state.data);
            });

        history.pushState(
            {
                data: null,
                name,
                state: "loading",
            },
            "",
            newUrl,
        );
        loading();
    };
};

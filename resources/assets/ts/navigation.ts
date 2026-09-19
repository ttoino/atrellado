import { apiFetch } from "./api";

export interface Route<Data> {
    data: Data;
    name: string;
    state: "loading" | "not ok" | "ok";
}

export const navigation = (
    name: string,
    newUrl: string,
    onNavigate: () => any,
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

export const ajaxNavigation = <Data, Params extends Parameters<any>>(
    name: string,
    fn: (...params: Params) => ReturnType<typeof apiFetch>,
    ok: (response: Data) => any,
    notOk: (response: any) => any,
    loading: () => any,
) => {
    window.addEventListener("popstate", (e) => {
        if (e.state.name != name) return;
        if (e.state.state == "ok") return ok(e.state.data);
        if (e.state.state == "not ok") return notOk(e.state.data);
        if (e.state.state == "loading") return loading();
    });

    return (newUrl: string, ...params: Params) => {
        fn(...params)
            .then(async (r) => {
                const state: Route<any> = {
                    data: await r.json(),
                    name,
                    state: "ok",
                };

                if (history.state.name == name)
                    history.replaceState(state, "", newUrl);

                if (r.ok) ok(state.data);
                else notOk(state.data);
            })
            .catch(async (r) => {
                const state: Route<any> = {
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

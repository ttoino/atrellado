import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            publicDirectory: "public",
            input: [
                "resources/assets/ts/app.ts",
                "resources/assets/sass/app.scss",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            // The bootstrap-icons scss references its fonts as
            // url("./fonts/..."); alias so vite emits them as assets.
            "./fonts": path.resolve(
                __dirname,
                "node_modules/bootstrap-icons/font/fonts"
            ),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 5.3 still uses @import internally; silence the
                // deprecation until the bootstrap 6 @use migration.
                quietDeps: true,
                silenceDeprecations: ["import"],
            },
        },
    },
});

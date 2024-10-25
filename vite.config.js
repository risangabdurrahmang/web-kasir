import { defineConfig } from "vite";
import laravel, { refreshPaths } from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: [...refreshPaths, "app/Livewire/**"],
        }),
    ],
    build: {
        minify: true,
        sourcemap: false,

        rollupOptions: {
            output: {
                manualChunks: (path) => {
                    if (path.includes("codemirror")) {
                        return "codemirror";
                    }
                    if (path.includes("node_modules")) {
                        return "vendor";
                    }
                },
            },
        },
    },
});

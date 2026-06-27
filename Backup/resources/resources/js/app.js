import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import "../css/app.css";

createInertiaApp({
    title: (t) => t ? `${t} - Aegis` : "Aegis",
    resolve: (name) => {
        const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
    progress: { color: "#C4A96A" },
});
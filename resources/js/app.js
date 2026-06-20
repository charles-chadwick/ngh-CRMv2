//
// noinspection JSIgnoredPromiseFromCall

import "./echo";
import { createInertiaApp } from "@inertiajs/vue3";
import { ZiggyVue } from "ziggy-js";

createInertiaApp({
    withApp(app) {
        app.use(ZiggyVue);
    },
})

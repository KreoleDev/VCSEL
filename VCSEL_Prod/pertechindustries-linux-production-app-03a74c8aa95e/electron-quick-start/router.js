const routes = [
    { path: '/', component: selectProductPage },
    { path: '/select-tla', component: selectTLAPage },
    { path: '/select-mode', component: selectModePage },
    { path: '/config', component: configPage }
];

const router = new VueRouter({
    routes // short for `routes: routes`
});
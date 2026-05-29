const routes = [
    { path: '/login', component: loginPage },
    { path: '/', component: selectProductPage, meta: { requiresAuth: true } },
    { path: '/select-tla', component: selectTLAPage, meta: { requiresAuth: true } },
    { path: '/select-mode', component: selectModePage, meta: { requiresAuth: true } },
    { path: '/config', component: configPage }
];

const router = new VueRouter({
    routes // short for `routes: routes`
});

router.beforeEach(function(to, from, next) {
    var isAuthenticated = sessionStorage.getItem('pertechAuthenticated') === '1' && !!sessionStorage.getItem('pertechUserId');

    if(to.matched.some(function(record) { return record.meta.requiresAuth; }) && !isAuthenticated) {
        next('/login');
    } else if(to.path === '/login' && isAuthenticated) {
        next('/');
    } else {
        next();
    }
});

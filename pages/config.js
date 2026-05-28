window.configPage = Vue.component('configPage', function (resolve, reject) {
    resolve({
        store,
        template: `
            <div style="padding:20px;">
                <label>Server Base URL: </label>
                <input type="text" v-model="serverIP" style="width:250px" />
                <button @click="save()">OK</button>
            </div>
        `,
        data: function () {
            return {
                serverIP: ""
            }
        },
        mounted: function() {
            var self = this;
            self.serverIP = CFG_DATA_SERVICE_URL;
        },
        methods: {
            //------------------------------------------------------------------------------------------------------
            save: function() {
                var self = this;
                console.log('save');
                CFG_DATA_SERVICE_URL = self.serverIP.replace(/\/?$/, '/');
                localStorage.setItem("cfgDataServiceURL", CFG_DATA_SERVICE_URL);
                router.push('/');
            }
            //------------------------------------------------------------------------------------------------------
        }
    });
});
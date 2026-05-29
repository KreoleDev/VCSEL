const loginPage = Vue.component('loginPage', function (resolve, reject) {
    resolve({
        store,
        template: `
            <div>
                <div id="loadingSlate" v-show="showLoading">
                    <div></div>
                </div>
                <div id="loadingMessage" v-show="showLoading">{{ loadingMessage }}</div>

                <div id="errorPrompt" v-show="showErrorPrompt">
                    <div class="errorWindow">
                        {{ errorPromptMessage }}
                        <button v-on:click="closeErrorPrompt()">OK</button>
                    </div>
                </div>

                <div id="mainContent" class="loginContent">
                    <div id="loginPanel">
                        <div class="loginBrand">
                            <div id="loginLogo" aria-label="Pertech"></div>
                            <div class="loginProductType">Pertech VCSEL Production Test System</div>
                        </div>
                        <h1>Sign in</h1>
                        <form v-on:submit.prevent="login()">
                            <div class="loginField">
                                <label>Username</label>
                                <input type="text" v-model="username" ref="usernameInput" autocomplete="username" placeholder="Enter username">
                            </div>
                            <div class="loginField">
                                <label>Password</label>
                                <input type="password" v-model="password" autocomplete="current-password" placeholder="Enter password">
                            </div>
                            <button type="submit" v-bind:disabled="showLoading">Sign in</button>
                        </form>
                    </div>
                </div>
            </div>
        `,
        data: function () {
            return {
                showLoading: false,
                loadingMessage: 'Checking login...',
                showErrorPrompt: false,
                errorPromptMessage: '',
                username: '',
                password: ''
            }
        },
        mounted: function() {
            var self = this;
            Velocity(document.getElementById("mainContent"), { opacity: 1 }, { duration: 400, complete: function() {
                if(self.$refs.usernameInput) {
                    self.$refs.usernameInput.focus();
                }
            }});
        },
        methods: {
            //------------------------------------------------------------------------------------------------------
            login: function() {
                var self = this;

                if(self.username == '' || self.password == '') {
                    self.errorPromptMessage = 'Please enter your username and password.';
                    self.showErrorPrompt = true;
                    aniShowErrorPrompt().then(() => { }, () => { });
                    return;
                }

                self.showLoading = true;
                aniShowLoading().then(() => {
                    this.connectionInst = new serverRequest(CFG_DATA_SERVICE_URL);
                    this.connectionInst.postToServer({
                        'mode': 'login',
                        'username': self.username,
                        'password': self.password
                    }, 'login/').then((response) => {
                        if(response && response.authenticated == true) {
                            var displayName = response.display_name || self.username;
                            var username = response.username || self.username;
                            var userId = response.user_id !== undefined && response.user_id !== null ? response.user_id : '';
                            if(userId === '') {
                                self.showLoginError('Login succeeded but the server did not return a user ID.');
                                return;
                            }
                            sessionStorage.setItem('pertechAuthenticated', '1');
                            sessionStorage.setItem('pertechUsername', username);
                            sessionStorage.setItem('pertechDisplayName', displayName);
                            sessionStorage.setItem('pertechUserId', userId);
                            window.app.isAuthenticated = true;
                            window.app.currentUser = username;
                            window.app.currentUserId = userId;
                            aniHideLoading().then(() => {
                                self.showLoading = false;
                                self.$router.push('/');
                            }, () => { });
                        } else {
                            self.showLoginError('Invalid username or password.');
                        }
                    }, () => {
                        self.showLoginError('Cannot communicate with server address: ' + CFG_DATA_SERVICE_URL + 'login/');
                    });
                }, () => { });
            },
            //------------------------------------------------------------------------------------------------------
            showLoginError: function(message) {
                var self = this;
                self.errorPromptMessage = message;
                aniHideLoading().then(() => {
                    self.showLoading = false;
                    self.showErrorPrompt = true;
                    aniShowErrorPrompt().then(() => { }, () => { });
                }, () => { });
            },
            //------------------------------------------------------------------------------------------------------
            closeErrorPrompt: function() {
                var self = this;
                aniHideErrorPrompt().then(() => {
                    self.showErrorPrompt = false;
                }, () => { });
            },
            //------------------------------------------------------------------------------------------------------
        }
    });
});

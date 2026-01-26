<template>
    <div class="bg-light min-vh-100 d-flex flex-row align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-group d-block d-md-flex row">
                        <div class="card col-md-7 p-4 mb-0">
                            <div class="card-body">
                                <h3>Login</h3>
                                <p class="text-medium-emphasis">Sign In to your account</p>
                                <div class="input-group mb-3">
                                    <span class="input-group-text">
                                        <i class="bi-person"></i>
                                    </span>
                                    <input class="form-control" type="text" placeholder="Username" v-model="username">
                                </div>

                                <div class="input-group mb-4">
                                    <span class="input-group-text">
                                        <i class="bi-key"></i>
                                    </span>
                                    <input class="form-control" type="password" placeholder="Password" v-on:keyup.enter="login" v-model="password">
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <button class="btn btn-primary px-4" type="button" @click="login()">Login</button>
                                    </div>

                                    <div class="col-6 text-end">
                                        <!--<button class="btn btn-link px-0" type="button">Forgot password?</button>-->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card col-md-5 text-white bg-primary py-5">
                            <div class="card-body text-center">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <div>
                                        <h2>Strive</h2>
                                    </div>
                                    <div>
                                        <p>Amur Financial Group</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../mixins/util'

export default {
    mixins: [util],
    data() {
        return {
            username: '',
            password: ''
        }
    },
    methods: {
        login: function() {
            let data = {
                username: this.username,
                password: this.password
            }

            this.axios({
                method: 'post',
                url: 'api/login',
                data: data
            })
            .then(response => {
                if(this.checkApiResponse(response)) {
                    window.location.href = '/'
                } else {
                    this.alertMessage = response.data.message
                    this.showAlert('error')
                    this.password = ''
                }
            })
            .catch(error => {
                console.log(error)
                this.alertMessage = 'Error'
                this.showAlert('error')
            })
        }
    }
}
</script>
`<template>
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <div>
                    NSF to NetSuite
                </div>

                <div class="ms-auto">
                </div>
            </div>
        </div>
    
        <div class="card-body">
            <div class="pb-2">
                Upload the NSF bank file here to convert it to NetSuite format
            </div>

            <div>
                <input class="form-control" type="file"
                    ref="file"
                    @change="handleUploadFile()"
                    hidden
                />
                <button type="button" class="btn btn-secondary"
                    @click="uploadFile()"
                >
                    <i class="bi-upload me-1"></i>Upload File
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { util } from '../../mixins/util'
import ConfirmationDialog from '../../components/ConfirmationDialog'

export default {
    mixins: [util],
    emits: ['events'],
    components: { ConfirmationDialog },
    data() {
        return {
            
        }
    },
    mounted() {
        
    },
    methods: {
        uploadFile: function() {
            this.$refs['file'].click()
        },

        handleUploadFile: function() {
            this.showPreLoader()

            let file = this.$refs['file'].files[0]
            
            if((file.size / 1024 / 1024) > 20) {
                this.alertMessage = 'Error - The maximum file size allowed for upload is 20MB'
                this.showAlert('error')
                this.hidePreLoader()
                this.$refs['file'].value = null
                return
            }

            if(file.size <= 0) {
                this.alertMessage = 'Error - File cannot be empty'
                this.showAlert('error')
                this.hidePreLoader()
                this.$refs['file'].value = null
                return
            }

            let data = JSON.stringify({
                any: null
            })
            
            let blob = new Blob([data], {
                type: 'application/json'
            })

            let formData = new FormData();

            formData.append('content', blob)
            formData.append('file', file)
            
            this.axios.post(
                'web/finance/nsf-to-netsuite',
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            )
            .then(response => {
                this.alertMessage = response.data.message
                this.showAlert(response.data.status)

                if(this.checkApiResponse(response)) {
                    const link = document.createElement('a')
                    link.href = 'data:text/csv;base64,' + response.data.data.file
                    link.setAttribute('download', response.data.data.name)
                    document.body.appendChild(link)
                    link.click()
                }                
            })
            .catch(error => {
                this.alertMessage = error
                this.showAlert('error')
            })
            .finally(() => {
                this.$refs['file'].value = null
                this.hidePreLoader()
            })
        }
    }
}
</script>`
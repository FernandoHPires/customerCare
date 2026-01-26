<template>
    <input type="text" class="form-control"
        :value="modelValue"
        @input="showSource"
        @blur="checkList" />
    <div class="source-autocomplete">
        <ul class="list-group source-list-handler" @mouseenter="listShown" @mouseleave="listHide">
            <li @click="selectSource(list)" v-for="list in listValue" :key="list.id" class="list-group-item source-list-item">{{ list.source }}</li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'source-input',
    props: {
        modelValue: String,
        sources: Array
    },
    emits: [ "sourceSelected", "update:modelValue" ],
    data () {
        return {
            retList: [],
            isActive: false,
            listHovered: false,
        }
    },
    methods: {
        showSource (event) {
            this.isActive = true;
            this.retList = []
            let inputs = event.target.value || ""
            let options = this.sources || []

            for( let i = 0; i < options.length; i++ ) {
                if( options[i].source.substr(0, inputs.length).toLowerCase() === inputs.toLowerCase() ) {
                    if( this.retList.length < 5 ) {
                        this.retList.push(options[i])
                    }
                }
            }

            this.$emit("update:modelValue", inputs)

        },
        selectSource (val) {
            this.retList = []
            this.isActive = false;
            this.listHovered = false;

            this.$emit("update:modelValue", val.source)
            this.$emit("sourceSelected", val)
        },
        checkList() {
            if( this.isActive ) {
                if( !this.listHovered ) {
                    this.isActive = false
                    this.retList = [];
                    this.$emit("update:modelValue", "")
                }
            } else {
                this.$emit("update:modelValue", "")
                this.retList = []
            }
        },
        listShown(){
            this.isActive = true;
            this.listHovered = true;
        },
        listHide(){
            this.isActive = false
            this.listHovered = false

            this.retList = []

            this.$emit("update:modelValue", "")
        }
    },
    computed: {
        listValue (){
            return this.retList
        }
    }
}
</script>

<style lang="scss" scoped>
.contact-center-app {
    input, select {
        color: #222;
        font-weight: 600;
    }

    .source-autocomplete {
        position: relative;

        .source-list-handler {
            position: absolute;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            /*position the autocomplete items to be the same width as the container:*/
            top: 100%;
            left: 0;
            right: 0;

            .source-list-item {
                cursor: pointer;
                &:hover{
                    background-color: #a0d1ad;
                }
            }
        }
    }
}
</style>
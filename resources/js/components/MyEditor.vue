<template>
    <div class="d-flex">
        <div class="menubar me-1 d-flex">
            <button type="button" class="btn btn-light me-1" @click="setBold" v-tooltip="{content: 'Bold'}">
                <i class="bi bi-type-bold"></i>
            </button>

            <button type="button" class="btn btn-light me-1" @click="setItalic" v-tooltip="{content: 'Italic'}">
                <i class="bi bi-type-italic"></i>
            </button>

            <button type="button" class="btn btn-light me-1" @click="setUnderline" v-tooltip="{content: 'Underline'}">
                <i class="bi bi-type-underline"></i>
            </button>

            <div class="dropdown me-1" v-tooltip="{content: 'Set text color'}">
                <a class="btn btn-light dropdown-toggle" href="#" role="button" data-coreui-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-marker-tip me-1"></i>
                </a>

                <ul class="dropdown-menu" style="--cui-dropdown-min-width: 0px">
                    <li v-for="color in colors" :key="color.color">
                        <a class="dropdown-item" href="#" @click="setTextColor(color.color)">
                            <i :style="'color: ' + color.color" class="bi bi-circle-fill"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="dropdown me-1" v-tooltip="{content: 'Set background color'}">
                <a class="btn btn-light dropdown-toggle" href="#" role="button" data-coreui-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-highlighter me-1"></i>
                </a>

                <ul class="dropdown-menu" style="--cui-dropdown-min-width: 0px">
                    <li v-for="color in colors" :key="color.color">
                        <a class="dropdown-item" href="#" @click="setBackgroundColor(color.color)">
                            <i :style="'color: ' + color.color" class="bi bi-circle-fill"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="dropdown" v-tooltip="{content: 'Font size'}">
                <a class="btn btn-light dropdown-toggle" href="#" role="button" data-coreui-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-fonts me-1"></i>
                </a>

                <ul class="dropdown-menu" style="--cui-dropdown-min-width: 0px">
                    <li><a class="dropdown-item" href="#" @click="setTextSize(8)">8</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(10)">10</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(12)">12</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(14)">14</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(16)">16</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(20)">20</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(24)">24</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(28)">28</a></li>
                    <li><a class="dropdown-item" href="#" @click="setTextSize(32)">32</a></li>
                </ul>
            </div>
        </div>

        <div class="menubar me-1">
            <button type="button" class="btn btn-light" @click="removeFormat()" v-tooltip="{content: 'Remove text formatting'}">
                <i class="bi bi-eraser-fill"></i>
            </button>
        </div>

        <div class="menubar me-1">
            <button 
                type="button" 
                class="btn btn-light" 
                @click="validateWebAddress()" v-tooltip="{content: 'Insert Web Address'}">
                <i class="bi bi-browser-chrome"></i>
                Insert Web Address
            </button>
        </div>

    </div>

    <div class="mt-2" v-if="editWebAddress">
        <div class="card card-body">
            <div class="d-flex align-items-end flex-wrap">

                <div class="form-group px-1 col-3">
                    <label> <strong>Enter the WEB ADDRESS for the link</strong></label>
                    <input v-model="webAddress" type="text" class="form-control">
                </div>

                <div class="form-group px-1 col-3">
                    <label> <strong>Enter the TEXT for the link</strong></label>
                    <input v-model="webTitle" type="text" class="form-control">
                </div>

                <div>
                    <button type="button" class="btn btn-outline-dark me-1" @click="cancelWebAddress()">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>

                    <button type="button" class="btn btn-success" @click="saveWebAddress()">
                        <i class="bi bi-save me-1"></i>Insert
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div>
        <p
            contenteditable="true"
            class="editable mt-1"
            style="font-family: monospace;overflow-x: auto;"
            @paste="removeAllTags($event)"
            @keyup.stop="updateParent"
            :ref="editId"
            v-html="note">
        </p>
    </div>



</template>

<script>
import { util } from '../mixins/util'

export default {
    mixins: [util],
    emits: ['change'],
    props: ['note'],

    data() {
        return {
            id: '',
            editId: "edit" + this.id,
            colors: [
                { color: '#000000' },
                { color: '#ff0000' },
                { color: '#ffff00' },
                { color: '#00ff00' },
                { color: '#0000ff' },
                { color: '#cc00ff' },
                { color: '#ffa500' },
                { color: '#00ffff' },                
                { color: '#ffffff' },
            ],
            editWebAddress: false,
            webAddress: '',
            webTitle: '',
            savedSelection: null
        };
    },

    beforeMount: function() {
        this.id = this.id ? this.id : this.generateId()
        this.editId = "edit" + this.id
    },

    methods: {
        generateId: function() {
            return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15)
        },

        updateParent: function() {
            console.log(this.$refs[this.editId].innerHTML)
            this.$emit('change', this.$refs[this.editId].innerHTML)
        },

        setBold: function(e) {
            e.preventDefault()
            document.execCommand('bold', false, null)
            this.updateParent()
        },

        setItalic: function(e) {
            e.preventDefault()
            document.execCommand('italic', false, null)
            this.updateParent()
        },

        setUnderline: function(e) {
            e.preventDefault()
            document.execCommand('underline', false, null)
            this.updateParent()
        },

        setTextColor: function(color) {
            document.execCommand('foreColor', false, color)
            this.updateParent()
        },

        setBackgroundColor: function(color) {
            document.execCommand('backColor', false, color)
            this.updateParent()
        },

        setTextSize: function(size) {
            var sel, range, originalText, replacementText, newNode
            if (window.getSelection) {
                originalText = window.getSelection().toString()
                originalText.replace(/<span\b[^>]*>(.*?)<\/span>/gi, '')
                newNode = document.createElement("span")
                newNode.style.fontSize = size + "px"
                newNode.innerHTML = originalText
                sel = window.getSelection()
                if (sel.rangeCount) {
                    range = sel.getRangeAt(0)
                    range.deleteContents()
                    range.insertNode(newNode)
                }
            } else if (document.selection && document.selection.createRange) {
                range = document.selection.createRange()
                range.text = replacementText
            }
            this.updateParent()
        },

        newLine: function(e) {
            if(e.keyCode === 13) {
                e.preventDefault();
                this.insertBr()
                //console.log('new line', e)
            }
        },

        removeFormat: function() {
            var content = this.$refs[this.editId].innerHTML
            content = this.removeTag(content, 'b')
            content = this.removeTag(content, 'i')
            content = this.removeTag(content, 'u')
            content = this.removeTag(content, 'font')
            content = this.removeTag(content, 'div')
            content = this.removeTag(content, 'ul')
            content = this.removeTag(content, 'ol')
            content = this.removeTag(content, 'li')
            content = this.removeTag(content, 'span')
            content = this.removeTagAndContent(content, 'xml')
            this.$refs[this.editId].innerHTML = content
            this.updateParent()
        },

        removeTag: function(text, tagName) {
            var parser = new DOMParser()
            var htmlDoc = parser.parseFromString(text, 'text/html')
            var elements = htmlDoc.getElementsByTagName(tagName)

            // Replace each element with its own textContent
            for (var i = elements.length - 1; i >= 0; i--) {
                var parent = elements[i].parentNode
                while(elements[i].firstChild) {
                    parent.insertBefore(elements[i].firstChild, elements[i])
                }
                parent.removeChild(elements[i])
            }

            return htmlDoc.body.innerHTML
        },

        removeTagAndContent: function(text, tagName) {
            var parser = new DOMParser();
            var htmlDoc = parser.parseFromString(text, 'text/html');
            var elements = htmlDoc.getElementsByTagName(tagName);

            // Remove each element entirely
            for(var i = elements.length - 1; i >= 0; i--) {
                elements[i].parentNode.removeChild(elements[i]);
            }

            return htmlDoc.body.innerHTML;
        },

        removeAllTags: function(e) {
            e.preventDefault()
            var text = e.clipboardData.getData("text/plain")
            //text = text.replace(/\n/g, '<br>')
            //text = text.replace(/ /g, "_")
            this.replaceSelectedText(text)
            this.updateParent()
        },

        replaceSelectedText: function(replacementText) {
            console.log('replacementText', replacementText)

            var sel, range
            if(window.getSelection) {
                sel = window.getSelection()
                if(sel.rangeCount) {
                    range = sel.getRangeAt(0)
                    range.deleteContents()

                    //let el = document.createElement("div")
                    //el.innerHTML = replacementText
                    //range.insertNode(el)

                    //let el2 = document.createTextNode('test')
                    //range.insertNode(el2)

                    //let el3 = document.createElement("div")
                    //range.insertNode(el3)

                    //let el = document.createTextNode(replacementText)
                    //range.insertNode(el)

                    var el
                    var rows = replacementText.split('\n')
                    rows.reverse()
                    for(var i = 0; i < rows.length; i++) {
                        el = document.createTextNode(rows[i])
                        range.insertNode(el)

                        if(i < rows.length - 1) {
                            el = document.createElement("br")
                            range.insertNode(el)
                        }
                    }

                    const newRange = document.createRange()
                    //newRange.selectNodeContents(el)
                    newRange.collapse(false)
                    
                    sel.removeAllRanges()
                    sel.addRange(newRange)
                }
            } else if (document.selection && document.selection.createRange) {
                range = document.selection.createRange()
                range.text = replacementText
            }
        },

        insertBr: function() {
            var sel, range;
            if(window.getSelection && (sel = window.getSelection()).rangeCount) {
                range = sel.getRangeAt(0);
                range.collapse(true);
                var span = document.createElement("br");
                range.insertNode(span);

                // Move the caret immediately after the inserted span
                range.setStartAfter(span);
                range.collapse(true);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        },
        validateWebAddress: function() {
            this.saveSelection()
            this.editWebAddress = true
            this.webAddress = 'http://'
            this.webTitle = 'Link to Property'
        },
        cancelWebAddress: function() {
            this.editWebAddress = false
            this.webAddress = 'http://'
            this.webTitle = 'Link to Property'            
        },
        saveSelection: function() {
            const sel = window.getSelection()
            if (sel.rangeCount > 0) {
                this.savedSelection = sel.getRangeAt(0)
            }
        },

        restoreSelection: function() {
            if (this.savedSelection) {
                const sel = window.getSelection()
                sel.removeAllRanges()
                sel.addRange(this.savedSelection)
            }
        },        
        saveWebAddress: function() {

            if (this.webAddress === '' || this.webTitle === '') {
                this.alertMessage = 'Web Address and Text for the link are required'
                this.showAlert('error')
                return
            }

            if (this.webAddress.trim().toLowerCase() === 'http://' || this.webAddress.trim().toLowerCase() === 'https://') {
                this.alertMessage = 'Web Address is required';
                this.showAlert('error');
                return;
            }

            this.restoreSelection()

            let url = this.webAddress.trim()
            if (!url.startsWith('http://') && !url.startsWith('https://')) {
                url = 'https://' + url
            }

            let newNode = document.createElement("a")
            newNode.href = url
            newNode.innerHTML = this.webTitle
            newNode.style.color = "blue"
            newNode.setAttribute("target", "_blank")
            newNode.setAttribute("rel", "noopener noreferrer")

            let sel = window.getSelection()
            if (sel.rangeCount) {
                let range = sel.getRangeAt(0)
                range.deleteContents()
                range.insertNode(newNode)
            }

            this.updateParent()
            this.editWebAddress = false
            this.webAddress = 'http://'
            this.webTitle = 'Link to Property'
            this.savedSelection = null
        }
    },
}
</script>

<style scoped>
.editable {
    border: 1px solid #ccc;
    padding: 5px;
    min-height: 100px;
    border-radius: 5px;
    height: 60vh;
    overflow-y: auto;
    overflow-x: hidden;
    font-family: monospace, 'Courier New', Courier;
}
.menubar {
    border: 1px solid #ccc;
    border-radius: 5px;
}
</style>

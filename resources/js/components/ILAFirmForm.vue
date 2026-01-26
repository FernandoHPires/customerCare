<template>
    <div class="modal-body">
        <div class="row d-flex align-items-end">
            <div class="col-4">
                <label class="form-label mb-0">Firm Name</label>
                <input v-model="localFirmData.firmName" type="text" class="form-control">
            </div>
            <div class="col-4">
                <label class="form-label mb-0">Name</label>
                <input v-model="localFirmData.name" type="text" class="form-control">
            </div>
            <div class="col-4">
                <label class="form-label mb-0">Position</label>
                <select v-model="localFirmData.position" class="form-select">
                    <option value="Lawyer">Lawyer</option>
                    <option value="Notary">Notary</option>
                </select>
            </div>
        </div>

        <div class="row d-flex align-items-end">
            <div class="col-4">
                <label class="form-label mb-0">Telephone</label>
                <input v-model="localFirmData.telephone" type="text" class="form-control">
            </div>
            <div class="col-4">
                <label class="form-label mb-0">Fax</label>
                <input v-model="localFirmData.fax" type="text" class="form-control">
            </div>
            <div class="col-4">
                <label class="form-label mb-0">Email</label>
                <input v-model="localFirmData.email" type="text" class="form-control">
            </div>
        </div>

        <div class="row d-flex align-items-end">
            <div class="col-3">
                <label class="form-label mb-0">Unit Number</label>
                <input v-model="localFirmData.unitNumber" type="text" class="form-control">
            </div>
            <div class="col-3">
                <label class="form-label mb-0">Street Number</label>
                <input v-model="localFirmData.streetNumber" type="text" class="form-control">
            </div>
            <div class="col-3">
                <label class="form-label mb-0">Street Name</label>
                <input v-model="localFirmData.streetName" type="text" class="form-control">
            </div>
            <div class="col-3">
                <label class="form-label mb-0">Street Type</label>
                <select v-model="localFirmData.streetType" class="form-select">
                    <option value=""></option>
                    <option value="AVE">AVE</option>
                    <option value="BLVD">BLVD</option>
                    <option value="CRES">CRES</option>
                    <option value="CRT">CRT</option>
                    <option value="DR">DR</option>
                    <option value="HWY">HWY</option>
                    <option value="LANE">LANE</option>
                    <option value="PL">PL</option>
                    <option value="RANG">RANG</option>
                    <option value="RD">RD</option>
                    <option value="ST">ST</option>
                    <option value="WAY">WAY</option>
                    <option value="OTHER">OTHER</option>
                </select>
            </div>
        </div>

        <div class="row d-flex align-items-end">
            <div class="col-3">
                <label class="form-label mb-0">Direction</label>
                <select v-model="localFirmData.direction" class="form-select">
                    <option value="N/A">N/A</option>
                    <option value="E">East</option>
                    <option value="W">West</option>
                    <option value="N">North</option>
                    <option value="NE">North East</option>
                    <option value="NW">North West</option>
                    <option value="S">South</option>
                    <option value="SE">South East</option>
                    <option value="SW">South West</option>
                </select>
            </div>
            <div class="col-3">
                <label class="form-label mb-0">City</label>
                <input v-model="localFirmData.city" type="text" class="form-control">
            </div>
            <div class="col-3">
                <label class="form-label mb-0">Province</label>
                <input v-model="localFirmData.province" type="text" class="form-control">
            </div>
            <div class="col-3">
                <label class="form-label m-0">Postal Code</label>
                <input v-model="localFirmData.postalCode" type="text" class="form-control">
            </div>
        </div>

        <div class="row d-flex align-items-end mt-3">
            <div class="col-2">
                <label class="form-label mb-0">Rating</label>
                <input v-model="localFirmData.rating" type="text" class="form-control">
            </div>
            <div class="col-2">
                <label class="form-labe mb-0">Use as ILA?</label>
                <select v-model="localFirmData.useAsIla" class="form-select">
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div class="col-8">
                <label class="form-label mb-0">Comments</label>
                <textarea v-model="localFirmData.comments" rows="1" class="form-control"></textarea>
            </div>
        </div>

        <div class="text-end mt-3">
            <button @click="$emit('cancel')" class="btn btn-outline-secondary me-2">
                <i class="bi bi-x-lg me-1"></i>Cancel
            </button>
            <button @click="saveFirm" class="btn btn-success">
                <i class="bi bi-save me-1"></i> {{ mode === 'edit' ? 'Save' : 'Add' }}
            </button>
        </div>
    </div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
    props: {
        firmData: Object,
        mode: String
    },
    emits: ['save', 'cancel'],
    setup(props, { emit }) {
        // Create a local copy of firmData to avoid modifying props directly
        const localFirmData = ref({ ...props.firmData })

        // Watch for prop changes (useful when switching between editing firms)
        watch(() => props.firmData, (newData) => {
            localFirmData.value = { ...newData }
        })

        const saveFirm = () => {
            emit('save', localFirmData.value)
        }

        return {
            localFirmData,
            saveFirm
        }
    }
}
</script>
import { modals } from "../mixins/modals"
import { global } from "../mixins/global"

export const util = {
    data() {
        return {
            defaultErrorMsg: "Error",
            loading: false,
            alertMessage: "",
            modals,
            global,
            /* calendar config */
            timezone: "UTC",
            modelConfig: {
                type: "string",
                mask: "iso",
                timeAdjust: "12:00:00",
            },
            papCompanies: [
                {
                    id: 5,
                    name: "Ryan Mortgage Income Fund Inc.",
                    done: 0,
                    pending: 0,
                    rejected: 0,
                },
                {
                    id: 16,
                    name: "Manchester Investments Inc.",
                    done: 0,
                    pending: 0,
                    rejected: 0,
                },
                {
                    id: 182,
                    name: "Blue Stripe Financial Ltd.",
                    done: 0,
                    pending: 0,
                    rejected: 0,
                },
                {
                    id: 183,
                    name: "Ryan Quebec Inc.",
                    done: 0,
                    pending: 0,
                    rejected: 0,
                },
                {
                    id: 1970,
                    name: "Amur Financial Group",
                    done: 0,
                    pending: 0,
                    rejected: 0,
                },
            ],
            monthNames: [
                { id: 1, value: "January" },
                { id: 2, value: "February" },
                { id: 3, value: "March" },
                { id: 4, value: "April" },
                { id: 5, value: "May" },
                { id: 6, value: "June" },
                { id: 7, value: "July" },
                { id: 8, value: "August" },
                { id: 9, value: "September" },
                { id: 10, value: "October" },
                { id: 11, value: "November" },
                { id: 12, value: "December" },
            ],
        };
    },
    methods: {
        getReferencePeriods: function (months) {
            var references = [];
            let date = new Date();

            for(let i = 0; i < months; i++) {
                let monthName = this.monthNames[date.getMonth()].value;

                references.push({
                    id: date.getFullYear() + "-" + (date.getMonth() + 1) + "-01",
                    name: monthName + " / " + date.getFullYear(),
                });

                date.setMonth(date.getMonth() - 1);
            }

            return references;
        },
        sort: function (key) {
            //data should be an array not object
            if (key === this.currentSort) {
                this.currentSortDir =
                    this.currentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }

            this.currentSort = key;
            this.data.sort((a, b) => {
                let modifier = 1;

                if (this.currentSortDir === "bi-sort-up") modifier = -1;
                if (a[this.currentSort] < b[this.currentSort])
                    return -1 * modifier;
                if (a[this.currentSort] > b[this.currentSort])
                    return 1 * modifier;

                return 0;
            });
        },
        filteredSort: function (key) {
            //filteredData should be an array not object
            if (key === this.currentSort) {
                this.currentSortDir =
                    this.currentSortDir === "bi-sort-down"
                        ? "bi-sort-up"
                        : "bi-sort-down";
            }

            this.currentSort = key;
            this.filteredData.sort((a, b) => {
                let modifier = 1;

                if (this.currentSortDir === "bi-sort-up") modifier = -1;
                if (a[this.currentSort] < b[this.currentSort])
                    return -1 * modifier;
                if (a[this.currentSort] > b[this.currentSort])
                    return 1 * modifier;

                return 0;
            });
        },
        getSortIcon: function (key) {
            if (this.currentSort == key) {
                if (this.currentSort == key) {
                    return this.currentSortDir + " text-dark";
                } else {
                    return this.currentSortDir + " text-gray";
                }
            } else {
                if (this.currentSort == key) {
                    return "bi-sort-down text-dark";
                } else {
                    return "bi-sort-down text-gray";
                }
            }
        },
        setUser(user) {
            this.global.state.vars["user"] = user;
        },
        getUser() {
            return this.global.state.vars["user"];
        },
        arrayStartsWith(array, start) {
            let starts = false;

            array.forEach(function (element) {
                if (start.startsWith(element)) {
                    starts = true;
                }
            });

            return starts;
        },
        getElementById(records, id) {
            return records.find(function (element, index) {
                return element.id === id;
            });
        },
        formatCurrency(str) {
            if (str === undefined || str === null) {
                return "";
            } else {
                let value = parseFloat(str.toString()).toFixed(0);

                if (value == 0) {
                    return "$0.00";
                } else if (value < 0) {
                    return (
                        "$(" +
                        (value * -1)
                            .toString()
                            .replace(/\B(?=(\d{3})+(?!\d))/g, ",") +
                        ")"
                    );
                } else {
                    return (
                        "$" +
                        value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    );
                }
            }
        },
        formatNumber(str) {
            if (str === undefined || str === null || str === NaN) {
                return "";
            } else {
                let value = parseFloat(str.toString()).toFixed(0);

                let formattedValue = ''
                if(value == 0) {
                    formattedValue = "0";
                } else if (value < 0) {
                    formattedValue = (
                        "-" +
                        (value * -1)
                            .toString()
                            .replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    );
                } else {
                    formattedValue = value
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                if(formattedValue === NaN || formattedValue === 'NaN') {
                    return ''
                }

                return formattedValue
            }
        },
        formatDecimal(str) {
            if (str === "" || str === undefined || str === null || isNaN(str)) {
                return "";
            } else {
                let value = parseFloat(str.toString()).toFixed(2);

                if (value == 0) {
                    return "0";
                } else if (value < 0) {
                    value = value * -1;
                    value = value.toFixed(2);
                    return (
                        "-" +
                        value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    );
                } else {
                    return value
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
            }
        },
        formatDate(date) {
            if (!date) return '';

            var year = date.toString().substring(0, 4)
            var month = date.toString().substring(5, 7)
            var day = date.toString().substring(8, 10)
            
            return `${month}/${day}/${year}`;
        },
        formatPhpDate(dateInput) {
            if (!dateInput) {
                return ""; // Handle empty/null/undefined cases
            }
        
            // If dateInput is an object with a date property, extract it
            let dateStr = typeof dateInput === "object" && dateInput.date ? dateInput.date.toString() : dateInput.toString();
        
            // Extract only the YYYY-MM-DD part in case there's a time component
            dateStr = dateStr.substring(0, 10);

            return dateStr.substring(5, 7) + "/" + dateStr.substring(8, 10) + "/" + dateStr.substring(0, 4);
        },
        formatPhpDateTime(str) {
            if(str === undefined || str === null || str.date === null) {
                return "";
            } else {
                return (
                    str.date.toString().substring(5, 7) +
                    "/" +
                    str.date.toString().substring(8, 10) +
                    "/" +
                    str.date.toString().substring(0, 4) +
                    " " +
                    str.date.toString().substring(11, 16)
                );
            }
        },
        convertDateFormat(str) {
            if(str == '0000-00-00' || str === undefined || str === null) return ''
            
            var tmp = str.split("-")
            return tmp[1] + "/" + tmp[2] + "/" + tmp[0]
        },
        checkSettings: function (response) {
            if (
                response.data.settings !== undefined &&
                response.data.settings
            ) {
                window.location.href = "/settings";
                return true;
            } else {
                return false;
            }
        },
        checkApiResponse: function (response) {
            if (
                response.status == 200 &&
                response.data.status !== undefined &&
                response.data.status == "success"
            ) {
                return true;
            }

            return false;
        },
        getErrorMessage: function (response, breakLine) {
            var errorMessage = "";

            if (response.data.errors !== undefined) {
                for (const key in response.data.errors) {
                    return (
                        (breakLine ? "<br>" : "") + response.data.errors[key]
                    );
                }
            }

            return errorMessage;
        },
        getValidationClass: function (obj) {
            if (obj == "" || obj === null) {
                return "is-invalid";
            } else {
                return "is-valid";
            }
        },
        /**
         * nested modals
         * 1. show 1st modal and use native backdrop (not the div backdrop)
         * 2. show 2nd modal and use div backdrop (hide the native backdrop)
         * 
         * when there are more than 1 modal on the same page,
         * the 2nd modal opened will use the div backdrop.
         * the 2nd modal neeeds to close the div backdrop when it is closed
         * otherwise, the div backdrop will remain visible (addEventListener)
         */
        showModal(modalId) {
            var modalObj = document.getElementById(modalId);

            modalObj.addEventListener('hidden.coreui.modal', function (event) {
                document.getElementById("backdrop").style.display = "none";
            })

            var modal;
            if (
                Object.keys(this.modals.state.modals).length > 0 &&
                !this.modals.state.modals.hasOwnProperty(modalId)
            ) {
                modalObj.style.zIndex = "8000";
                modal = new this.coreui.Modal(modalObj, {});

                //nested modals are not supported in Bootstrap
                //this workaround will fix the backdrop
                modal._backdrop._config.isVisible = false;
                this.showBackdrop();
            } else {
                modal = new this.coreui.Modal(modalObj, {});
            }

            modal.show();

            //to close the modal, should be used the same instance
            this.modals.state.modals[modalId] = modal;
        },
        hideModal(modalId) {
            this.hideBackdrop();
            this.modals.state.modals[modalId].hide();
            delete this.modals.state.modals[modalId];
        },
        showBackdrop() {
            document.getElementById("backdrop").style.display = "";
        },
        hideBackdrop() {
            document.getElementById("backdrop").style.display = "none";
        },
        showPreLoader() {
            this.loading = true;
            document.getElementById("preloader").style.display = "";
        },
        hidePreLoader() {
            this.loading = false;
            document.getElementById("preloader").style.display = "none";
        },
        showAlert: function (type) {
            if(type == "success") {
                document.getElementById("alert-message-success").innerHTML = this.alertMessage
                document.getElementById("alert-success").style.display = ""
                setTimeout(() => {
                    this.hideAlert("alert-success")
                }, 3000)

            } else if(type == "warning") {
                document.getElementById("alert-message-warning").innerHTML = this.alertMessage
                document.getElementById("alert-warning").style.display = ""
                setTimeout(() => {
                    this.hideAlert("alert-warning")
                }, 5000)

            } else {
                document.getElementById("alert-message-error").innerHTML = this.alertMessage
                document.getElementById("alert-error").style.display = ""
                setTimeout(() => {
                    this.hideAlert("alert-error")
                }, 4000)
            }
        },
        hideAlert: function (id) {
            document.getElementById(id).style.display = "none"
        },
        adjustedDate($date) {
            let d = new Date($date)
            return d.setDate(d.getDate() + 1)
        },
    },
};

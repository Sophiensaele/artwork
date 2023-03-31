<template>
    <jet-dialog-modal :show="editingChecklistTeams" @close="emitClose">
        <template #content>

            <img alt="" src="/Svgs/Overlays/illu_checklist_team_assign.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-2xl my-2">
                    Nutzer*innen zuweisen
                </div>

                <XIcon @click="emitClose"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>

                <div class="text-secondary tracking-tight leading-6 sub">
                    Tippe den Namen des Nutzer*innen ein, dem du die Checkliste zuweisen möchtest.
                </div>
                <div class="mt-10">
                    <!--   Search for Departments    -->
                    <div class="my-auto w-full">
                        <input id="departmentSearch" placeholder="Name"
                               v-model="userQuery"
                               type="text"
                               autocomplete="off"
                               class="pl-2 h-12 w-full focus:border-primary border border-2 border-gray-300 text-primary focus:outline-none focus:ring-0 placeholder-secondary"/>
                    </div>

                    <!--    Department Search Results    -->
                    <div class="absolute max-h-60 bg-primary shadow-lg text-sm flex flex-col w-9/12">
                        <button v-for="(user, index) in searchedUsers"
                                :key="index"
                                class="flex items-center cursor-pointer p-4 font-bold text-white text-left hover:border-l-4 hover:border-l-success border-l-4 border-l-primary"
                                @click="addUserToChecklist(user)">
                            <img class="h-5 w-5 mr-2 object-cover rounded-full"
                                 :src="user.profile_photo_url"
                                 alt=""/>
                            {{ user.first_name }} {{ user.last_name }}
                        </button>
                        <div v-if="userQuery && (searchedUsers.length === 0)"
                             key="no-item"
                             class="p-4 font-bold text-white">
                            Keine Ergebnisse gefunden
                        </div>
                    </div>
                </div>
                <!--    Team list    -->
                <div v-for="(user,index) in selectedUsers"
                     class="mt-4 font-bold text-primary flex"
                     :key="index">
                    <div class="flex items-center">
                        <img class="h-5 w-5 mr-2 object-cover rounded-full"
                             :src="user.profile_photo_url"
                             alt=""/>
                        {{ user.first_name }} {{ user.last_name }}
                    </div>
                    <button type="button" @click="removeUser(user)">
                        <span class="sr-only">User aus Checkliste entfernen</span>
                        <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error text-white bg-primary rounded-full"/>
                    </button>
                </div>

                <AddButton @click="submitUsers"
                           text="Zuweisen"
                           mode="modal"
                           class="mt-8 px-12 py-3" />

                <!-- <p v-if="error" class="text-red-800 text-xs">{{ error }}</p> -->
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>

import {XCircleIcon, XIcon} from '@heroicons/vue/outline';
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import JetDialogModal from "@/Jetstream/DialogModal";
import AddButton from "@/Layouts/Components/AddButton";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    name: 'AddChecklistUserModal',

    components: {
        XIcon,
        XCircleIcon,
        TeamIconCollection,
        JetDialogModal,
        AddButton
    },

    emits: ['closed'],

    props: ['checklistId', 'users', 'editingChecklistTeams'],

    data() {
        return {
            selectedUsers: [],
            searchedUsers: [],
            userQuery: null,
            error: null,
            checklist: useForm({

            })
        }
    },

    methods: {
        addUserToChecklist(user) {
            if (!this.selectedUsers.find((selected) => selected.id === user.id)) {
                this.selectedUsers.push(user);
            }
            this.userQuery = null;
            this.searchedUsers = [];
        },

        removeUser(user) {
            this.selectedUsers.splice(this.selectedUsers.indexOf(user),1);
        },

        async submitUsers() {
            await axios
                .patch(`/checklists/${this.checklistId}`, {
                    assigned_user_ids: this.selectedUsers.map((user) => user.id)
                })
                .then(response => this.emitClose())
                .catch(error => this.emitClose());
        },

        emitClose() {
            this.$emit('closed')
        },
    },

    watch: {
        userQuery: {
            handler() {
                if (!this.userQuery) {
                    return
                }
                axios.get('/users/search', {params: {query: this.userQuery}
                }).then(response => {
                    this.searchedUsers = response.data
                })
            },
        },
        users: {
            handler() {
                this.selectedUsers = this.users
            },
            deep: true
        },
    },
}
</script>

<style scoped>
</style>

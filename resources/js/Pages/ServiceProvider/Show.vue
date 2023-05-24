<template>
    <AppLayout title="Freelancer">
        <div class="max-w-screen-lg mt-12 ml-14 mr-40">
            <div class="flex justify-between w-full items-center">
                <div class="group block flex-shrink-0">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block h-16 w-16 rounded-full object-cover" :src="serviceProvider.profile_image" alt="" />
                        </div>
                        <div class="ml-3">
                            <h3 class="headline1">
                                {{ serviceProvider.provider_name }}
                            </h3>
                        </div>
                    </div>
                </div>
                <div>
                    <Menu as="div" class="my-auto relative">
                        <div>
                            <MenuButton class="flex">
                                <DotsVerticalIcon class="mr-3 ml-6 flex-shrink-0 h-6 w-6 text-gray-600 my-auto bg-buttonBlue/10 rounded-full" aria-hidden="true"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-right absolute p-4 mr-4 mt-2 w-80 shadow-lg bg-primary focus:outline-none">
                                <div>

                                    <MenuItem v-slot="{ active }">

                                        <a href="#" @click="openChangeTeamsModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Teamzugehörigkeit bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="deleteFromAllDepartments"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Nutzer*in aus allen Teams entfernen
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>

            <div class="mt-10">
                <div class="hidden sm:block">
                    <div class="">
                        <nav class="-mb-px flex space-x-8 uppercase xxsDark" aria-label="Tabs">
                            <div v-for="tab in tabs" :key="tab.name" @click="changeTab(tab.id)" :class="[tab.current ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']" :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}</div>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="mt-10">
                <div v-if="currentTab === 1">

                </div>
                <div v-if="currentTab === 2">

                </div>
                <!-- Persönliche Daten -->
                <div v-if="currentTab === 3">
                    <!-- Profilbild, Name, Nachname -->
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-4 flex items-center">
                        <div class="col-span-1">
                            <input
                                ref="photoInput"
                                type="file"
                                class="hidden"
                                @change="updatePhotoPreview"
                            >

                            <!-- Current Profile Photo -->
                            <div v-show="! photoPreview" class="mt-2">
                                <img :src="serviceProvider.profile_image" :alt="serviceProvider.provider_name" @click="selectNewPhoto" class="rounded-full h-20 w-20 object-cover cursor-pointer">
                            </div>

                            <!-- New Profile Photo Preview -->
                            <div v-show="photoPreview" class="mt-2" @click="selectNewPhoto">
                                <span
                                    class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                                    :style="'background-image: url(\'' + photoPreview + '\');'"
                                />
                            </div>

                        </div>
                        <div class="col-span-7">
                            <label for="first_name" class="xxsLight">Unternehmensname</label>
                            <div>
                                <input type="text" v-model="providerData.provider_name" :disabled="checkCanEdit" :readonly="checkCanEdit" name="first_name" id="first_name" class="block w-full border-b-2 border-transparent border-b-gray-200 py-1.5 text-gray-900 ring-0 ring-inset placeholder:text-gray-400 sm:text-sm sm:leading-6" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Vorname" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                        <div class="col-span-1">
                            <input type="text" readonly class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8 bg-gray-200" placeholder="Dienstleister" disabled value="Dienstleister" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="providerData.street" :disabled="checkCanEdit" :readonly="checkCanEdit" name="street" id="street" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Straße" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="providerData.zip_code" :disabled="checkCanEdit" :readonly="checkCanEdit" name="zip_code" id="zip_code" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="PLZ" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="providerData.location" :disabled="checkCanEdit" :readonly="checkCanEdit" name="location" id="location" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Ort" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="providerData.email" :disabled="checkCanEdit" :readonly="checkCanEdit" name="email" id="email" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Email" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="providerData.phone_number" :disabled="checkCanEdit" :readonly="checkCanEdit" name="phone_number" id="phone_number" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Telefonnummer" />
                        </div>
                        <div class="col-span-full">
                            <textarea rows="4" v-model="providerData.note" :disabled="checkCanEdit" :readonly="checkCanEdit" name="note" id="note" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Notiz" :class="checkCanEdit ? 'bg-gray-200' : ''" />
                        </div>
                    </div>

                    <AddButton class="mt-5 !ml-0" text="Änderung Speichern" type="secondary" @click="saveFreelancer" />


                    <div class="mt-10 mb-10">
                        <h3 class="headline3">
                            Ansprechpartner
                        </h3>

                        <ul role="list" class="divide-y divide-gray-100 mt-5" v-if="checkCanEdit">
                            <li v-for="contact in serviceProvider.contacts" :key="contact.email" class="flex justify-between gap-x-6 py-5">
                                <div class="flex gap-x-4">
                                    <div class="min-w-0 flex">
                                        <p class="text-sm font-semibold leading-6 text-gray-900">{{ contact.first_name }} {{ contact.last_name }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="w-fit">
                                        {{ contact.phone_number }}
                                    </span>
                                    |
                                    <span class="w-52">
                                        {{ contact.email }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                        <div v-else class="mt-10">
                            <div v-for="contact in serviceProvider.contacts">
                                <SingleContact :contact="contact"/>
                            </div>
                            <div>
                                <PlusCircleIcon class="w-5 h-5" @click="addContact" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>


<script>
import {defineComponent} from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import {DotsVerticalIcon, PencilAltIcon, PlusCircleIcon, TrashIcon} from "@heroicons/vue/outline";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {Inertia} from "@inertiajs/inertia";
import Permissions from "@/mixins/Permissions.vue";
import SingleContact from "@/Pages/ServiceProvider/Components/SingleContact.vue";

export default defineComponent({
    name: "Show",
    mixins: [Permissions],
    components: {
        SingleContact,
        AddButton,
        PencilAltIcon, DotsVerticalIcon, TrashIcon,
        AppLayout, Menu, MenuButton, MenuItems, MenuItem,
        PlusCircleIcon
    },
    props: ['serviceProvider'],
    data(){
        return {
            tabs: [
                { id: 1, name: 'Einsatzplan', href: '#', current: false },
                { id: 2, name: 'Konditionen', href: '#', current: false },
                { id: 3, name: 'unternehmensdaten', href: '#', current: true },
            ],
            currentTab: 3,
            providerData: useForm({
                provider_name: this.serviceProvider.provider_name,
                email: this.serviceProvider.email,
                phone_number: this.serviceProvider.phone_number,
                street: this.serviceProvider.street,
                zip_code: this.serviceProvider.zip_code,
                location: this.serviceProvider.location,
                note: this.serviceProvider.note,
            }),
            photoPreview: null,
        }
    },
    computed: {
        checkCanEdit(){
            return !(this.$can('can manage workers') || this.hasAdminRole());
        },
    },
    methods: {
        addContact(){
            Inertia.post(route('service-provider.contact.store', this.serviceProvider.id), {

            }, {
                preserveState: true, preserveScroll: true
            })
        },
        changeTab(tabId){
            this.tabs.forEach((tab) => {
                tab.current = tab.id === tabId;
                this.currentTab = tabId;
            })
        },
        saveFreelancer(){
            this.providerData.patch(route('service-provider.update', this.serviceProvider.id), {
                preserveState: true,
                preserveScroll: true
            })
        },
        selectNewPhoto(){
            if( this.$can('can manage workers') || this.hasAdminRole()){
                this.$refs.photoInput.click();
            }
        },
        updatePhotoPreview(){
            const photo = this.$refs.photoInput.files[0];


            if (! photo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };

            reader.readAsDataURL(photo);

            Inertia.post(route('service-provider.change.profile-image', this.serviceProvider.id),{
                profileImage: photo,
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
    }
})
</script>

<style scoped>

</style>
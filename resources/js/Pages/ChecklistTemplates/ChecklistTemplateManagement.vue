<template>
    <app-layout>
        <div class="max-w-screen-lg my-12 ml-14 mr-40">
            <div class="flex flex-1 flex-wrap">
                <div class="flex justify-between w-full">
                    <div class="flex">
                        <h2 class="text-3xl font-black font-lexend flex">Checklistenvorlagen</h2>
                        <Link :href="route('checklist_templates.create')">
                            <AddButton text="Neue Vorlage" mode="page"/>
                        </Link>
                        <div v-if="$page.props.can.show_hints" class="flex mt-1">
                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                            <span
                                class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Lege neue Checklistenvorlagen an</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                             class="cursor-pointer inset-y-0 mr-12">
                            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                        </div>
                        <div v-else class="flex items-center w-full w-64 mr-12">
                            <input id="templateSearch" v-model="template_query" type="text" autocomplete="off"
                                   class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 block w-full "
                                   placeholder="Suche nach Projekten"/>
                            <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                        </div>
                    </div>
                </div>
            </div>
            <ul role="list" class="mt-6 mb-32 w-full">
                <li v-if="template_query < 1" v-for="(template,index) in checklist_templates" :key="template.email"
                    class="py-3 flex justify-between">
                    <div class="flex">
                        <div class="my-auto w-full justify-start mr-6">
                            <div class="flex my-auto">
                                <Link :href="getEditHref(template)" class="text-lg mr-3 font-bold font-lexend text-primary">
                                    {{ template.name }} </Link>
                                <p class="ml-1 text-sm font-medium text-secondary my-auto"> angelegt am
                                    {{ template.created_at }} von
                                </p>
                                <img :data-tooltip-target="template.user.id" class="h-6 w-6 ml-2 my-auto rounded-full flex justify-start"
                                     :src="template.user.profile_photo_url"
                                     alt=""/>
                                <UserTooltip :user="template.user" />
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <div class="flex mr-8 items-center">
                            <Menu as="div" class="my-auto relative">
                                <div class="flex">
                                    <MenuButton
                                        class="flex">
                                        <DotsVerticalIcon
                                            class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                            aria-hidden="true"/>
                                    </MenuButton>
                                    <div v-if="$page.props.can.show_hints && index === 0"
                                         class="absolute flex w-40 ml-6">
                                        <div>
                                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                        </div>
                                        <div class="flex">
                                                    <span
                                                        class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite eine Vorlage</span>
                                        </div>
                                    </div>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-slot="{ active }">
                                                <a :href="getEditHref(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Bearbeiten
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="duplicateTemplate(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <DuplicateIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Duplizieren
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="openDeleteTemplateModal(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <TrashIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Löschen
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                </li>
                <li v-else v-for="(template,index) in template_search_results" :key="template.email"
                    class="py-3 flex justify-between">
                    <div class="flex">
                        <div class="my-auto w-full justify-start mr-6">
                            <div class="flex my-auto">
                                <p class="text-lg mr-3 font-bold font-lexend text-primary">
                                    {{ template.name }} </p>
                                <p class="ml-1 text-sm font-medium text-secondary my-auto"> angelegt am
                                    {{ template.created_at }} von
                                </p>
                                <img :data-tooltip-target="template.user.id" class="h-6 w-6 ml-2 my-auto rounded-full flex justify-start"
                                     :src="template.user.profile_photo_url"
                                     alt=""/>
                                <UserTooltip :user="template.user" />
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <div class="flex mr-8 items-center">
                            <Menu as="div" class="my-auto relative">
                                <div class="flex">
                                    <MenuButton
                                        class="flex">
                                        <DotsVerticalIcon
                                            class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                            aria-hidden="true"/>
                                    </MenuButton>
                                    <div v-if="$page.props.can.show_hints && index === 0"
                                         class="absolute flex w-40 ml-6">
                                        <div>
                                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                        </div>
                                        <div class="flex">
                                                    <span
                                                        class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite eine Vorlage</span>
                                        </div>
                                    </div>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-slot="{ active }">
                                                <a :href="getEditHref(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Bearbeiten
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="duplicateTemplate(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <DuplicateIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Duplizieren
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="openDeleteTemplateModal(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <TrashIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Löschen
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <!-- Delete Project Modal -->
        <jet-dialog-modal :show="showDeleteTemplateModal" @close="closeDeleteTemplateModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary text-3xl my-2">
                        Checklistenvorlage löschen
                    </div>
                    <XIcon @click="closeDeleteTemplateModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du die Checklistenvorlage {{ templateToDelete.name }} löschen möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteTemplate">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteTemplateModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        {{this.successHeading}}
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        {{this.successText}}
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal">
                            <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                        </button>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>

import  {Inertia} from "@inertiajs/inertia";
import AddButton from "@/Layouts/Components/AddButton";
import {SearchIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, DuplicateIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, PlusSmIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Link} from "@inertiajs/inertia-vue3";
import JetDialogModal from "@/Jetstream/DialogModal";
import UserTooltip from "@/Layouts/Components/UserTooltip";

export default {
    name: "Checklist Management",
    props: ['checklist_templates'],
    components: {
        AddButton,
        PlusSmIcon,
        SvgCollection,
        AppLayout,
        SearchIcon,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
        TrashIcon,
        DotsVerticalIcon,
        PencilAltIcon,
        DuplicateIcon,
        Link,
        JetDialogModal,
        XIcon,
        UserTooltip,
        CheckIcon
    },
    data() {
        return {
            templateToDelete: null,
            showDeleteTemplateModal: false,
            showSuccessModal: false,
            successHeading: '',
            successText:'',
            template_query: '',
            template_search_results:[],
            showSearchbar: false,
            duplicateForm: this.$inertia.form({
                _method: 'POST',
                name: "",
                //user who created the template
                user_id: this.$page.props.user.id,
                task_templates: [],
                departments: []
            }),
        }
    },
    methods: {
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.template_query = ''
        },
        getEditHref(template) {
            return route('checklist_templates.edit', {checklist_template: template.id});
        },
        openDeleteTemplateModal(template){
            this.templateToDelete = template;
            this.showDeleteTemplateModal = true;
        },
        closeDeleteTemplateModal(){
            this.showDeleteTemplateModal = false;
            this.templateToDelete = null;
            this.openSuccessModal('delete')
        },
        deleteTemplate(){
            Inertia.delete(`/checklist_templates/${this.templateToDelete.id}`);
            this.closeDeleteTemplateModal();
        },
        duplicateTemplate(templateToDuplicate){
            this.duplicateForm.name = templateToDuplicate.name + ' (Kopie)';
            this.duplicateForm.task_templates = templateToDuplicate.task_templates;
            this.duplicateForm.departments = templateToDuplicate.departments
            this.duplicateForm.post(route('checklist_templates.store'));
        },
        openSuccessModal(type){
            if(type === 'delete'){
                this.successHeading = 'Löschen erfolgreich';
                this.successText = 'Die Checklistenvorlage wurde erfolgreich gelöscht.';
            }else if(type === 'edit'){
                this.successHeading = 'Checklistenvorlage erfolgreich bearbeitet';
                this.successText = 'Die Änderungen wurden erfolgreich gespeichert.';
            }
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal(){
            this.showSuccessModal = false;
        },
    },
    setup() {
        return {}
    },
    watch: {
        template_query: {
            handler() {
                if (this.template_query.length > 0) {
                    axios.get('/checklist_templates/search', {
                        params: {query: this.template_query}
                    }).then(response => {
                        this.template_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
}
</script>

<style scoped>

</style>
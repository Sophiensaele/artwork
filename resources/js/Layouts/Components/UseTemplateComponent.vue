<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Vorlage einlesen" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            Vorlage einlesen
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        Um deine Arbeit einfacher zu machen, nutze eine Vorlage.
                    </h2>
                    <Listbox as="div" class="flex h-12 mr-2 w-full" v-model="selectedTemplate">
                        <ListboxButton
                            class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                            <div class="flex items-center my-auto">
                                        <span class="block truncate items-center ml-3 flex" v-if="selectedTemplate">
                                            <span>{{ selectedTemplate?.name }}</span>
                                        </span>
                                <span class="block truncate items-center ml-3 flex" v-else>
                                            <span> Vorlage aussuchen*</span>
                                        </span>
                                <span
                                    class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                        </span>
                            </div>
                        </ListboxButton>

                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions
                                class="absolute w-[90%] z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class="max-h-8"
                                               v-for="template in this.templates"
                                               :key="template.id"
                                               :value="template"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                        <div class="flex">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ template.name }}
                                                    </span>
                                        </div>
                                        <span
                                            :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>

                    <div class="flex justify-center">
                        <AddButton @click="useTemplate()" :disabled="selectedTemplate === null"
                                   :class="selectedTemplate === null ? 'bg-secondary hover:bg-secondary cursor-pointer-none' : ''"
                                   class="mt-8 py-3 flex" text="Vorlage einlesen"
                                   mode="modal"></AddButton>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";


import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import AddButton from "@/Layouts/Components/AddButton.vue";

export default {
    name: 'UseTemplateComponent',

    components: {
        AddButton,
        ListboxOptions,
        ListboxOption,
        ListboxButton,
        Listbox,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon
    },

    data() {
        return {
            selectedTemplate: null,
        }
    },

    props: ['projectId', 'templates'],

    emits: ['closed'],

    watch: {},

    methods: {
        openModal() {
        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
        useTemplate() {
            this.$inertia.post(route('project.budget.template.use', this.selectedTemplate.id), { project_id: this.projectId });
            this.closeModal(true);
        }
    },
}
</script>

<style scoped></style>
<template>
    <jet-dialog-modal :show="true" @close="closeModal()">

        <template #content>
            <img alt="Details" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow flex items-center headline1">
                            Details
                        </div>
                    </h1>
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-gray-200">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                       :aria-current="tab.current ? 'page' : undefined">
                                        {{ tab.name }}
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <!-- Commentary Tab -->
                    <div v-if="isCommentTab">

                        <textarea
                             placeholder="Was gibt es zu diesem Posten zu beachten?"
                             v-model="commentForm.comment" rows="4"
                             class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 inputMain pt-3 mb-8 placeholder-secondary  w-full"/>
                        <div>

                            <div class="my-6" v-for="comment in selectedSumDetail.comments"
                                 @mouseover="commentHovered = comment.id"
                                 @mouseout="commentHovered = null">
                                <div class="flex justify-between">
                                    <div class="flex items-center">
                                        <NewUserToolTip :id="comment.id" :user="comment.user" :height="8"
                                                        :width="8"></NewUserToolTip>
                                        <div class="ml-2 text-secondary"
                                             :class="commentHovered === comment.id ? 'text-primary':'text-secondary'">
                                            {{ formatDate(comment.created_at) }}
                                        </div>
                                    </div>
                                    <button v-show="commentHovered === comment.id && comment.user_id === $page.props.user.id" type="button"
                                            @click="deleteCommentFromCell(comment)">
                                        <span class="sr-only">Kommentar von Projekt entfernen</span>
                                        <XCircleIcon class="ml-2 h-7 w-7 hover:text-error"/>
                                    </button>
                                </div>
                                <div class="mt-2 mr-14 subpixel-antialiased text-primary font-semibold">
                                    {{ comment.comment }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <AddButton @click="addCommentToCell()" text="Speichern"
                                       :disabled="commentForm.comment === null && commentForm.comment === ''"
                                       :class="commentForm.comment === null || commentForm.comment === '' ? 'bg-secondary hover:bg-secondary' : ''"
                                       class="text-sm ml-0 px-24 py-5 xsWhiteBold"></AddButton>
                        </div>
                    </div>
                    <!-- Link Tab -->

                    <div v-if="isLinkTab">
                        <h2 class="xsLight mb-2 mt-4">
                            Behalte den Überblick über deine Finanzierungsquellen. Du kannst den Wert zur
                            Quelle entweder addieren oder subtrahieren.
                        </h2>

                        <div class="flex items-center justify-start my-6">
                            <input v-model="isLinked" type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[isLinked ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto text-sm"> Mit Finanzierungsquelle verlinken</p>
                        </div>

                        <div v-if="isLinked" class="flex w-full">
                            <div class="flex w-full">
                                <div class="relative w-full">
                                    <div class="w-full flex">
                                        <Listbox as="div" v-model="linkedType" id="linked_type" >
                                            <ListboxButton  class="inputMain w-12 h-10 cursor-pointer truncate flex p-2">
                                                <div class="flex-grow xsLight text-left subpixel-antialiased">
                                                    {{ linkedType.name }}
                                                </div>
                                                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                            </ListboxButton>
                                            <ListboxOptions class="w-12 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                                <ListboxOption v-for="type in linkTypes"
                                                               class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                               :key="type.name"
                                                               :value="type"
                                                               v-slot="{ active, selected }">
                                                    <div :class="[selected ? 'text-white' : '']">
                                                        {{ type.name }}
                                                    </div>
                                                    <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </Listbox>
                                        <input id="userSearch" v-model="moneySource_query" type="text" autocomplete="off"
                                               placeholder="Mit welcher Finanzierungsquelle willst du den Wert verlinken?"
                                               class="h-10 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100"
                                                leave-to-class="opacity-0">
                                        <div v-if="moneySource_search_results.length > 0 && moneySource_query.length > 0"
                                             class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                            <div class="border-gray-200">
                                                <div v-for="(moneySource, index) in moneySource_search_results" :key="index"
                                                     class="flex items-center cursor-pointer">
                                                    <div class="flex-1 text-sm py-4">
                                                        <p @click="selectMoneySource(moneySource)"
                                                           class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                            {{ moneySource.name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                    <div class="flex xsDark mt-2">
                                        Verlinkt mit:
                                        <div class="xsDark mx-2">
                                            {{selectedMoneySource?.name}}
                                        </div>
                                        als
                                        <div v-if="linkedType.type === 'EARNING'" class="xsDark mx-2">
                                            Einnahme
                                        </div>
                                        <div v-else class="xsDark mx-2">
                                            Ausgabe
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="flex justify-center">
                            <AddButton @click="updateMoneySourceLink()" :disabled="selectedMoneySource === null"
                                       class="mt-8 py-5 px-24 flex" text="Speichern"
                                       mode="modal"></AddButton>
                        </div>
                    </div>


                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions, RadioGroup, RadioGroupOption} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import {CheckIcon, ChevronDownIcon, PlusCircleIcon, XIcon} from '@heroicons/vue/outline';
import AddButton from "@/Layouts/Components/AddButton.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/inertia-vue3";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
const linkTypes = [
    {name: '+', type: 'EARNING'},
    {name: '-', type: 'COST'}
]
export default {
    name: 'SumDetailComponent',

    components: {
        NewUserToolTip,
        UserTooltip,
        AddButton,
        ListboxOptions,
        ListboxOption,
        ListboxButton,
        Listbox,
        RadioGroupOption,
        RadioGroup,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        PlusCircleIcon,
        XCircleIcon
    },

    data() {
        return {
            isLinked: this.selectedSumDetail.sum_money_source !== null,
            linkedType: this.selectedSumDetail.sum_money_source?.linked_type === 'EARNING' ? linkTypes[0] : linkTypes[1],
            selectedMoneySource: this.selectedSumDetail.sum_money_source?.money_source ?? null,
            linkTypes,
            isCommentTab: true,
            isLinkTab: false,
            cellComment: null,
            commentHovered: null,
            calculationHovered: null,
            commentForm: useForm({
                comment: '',
                commentable_id: this.selectedSumDetail.id,
                commentable_type: this.selectedSumDetail.class
            }),
            moneySource_query: '',
            moneySource_search_results: [],
        }
    },

    props: ['selectedSumDetail','projectId'],

    emits: ['closed'],
    watch: {
        moneySource_query: {
            handler() {
                if (this.moneySource_query.length > 0) {
                    axios.get('/money_sources/search', {
                        params: {query: this.moneySource_query, projectId: this.projectId}
                    }).then(response => {
                        this.moneySource_search_results = response.data.filter((moneySource) => moneySource.is_group === 0 || moneySource.is_group === false)
                    })
                }
            },
            deep: true
        },
    },
    computed: {
        tabs() {
            return [
                {name: 'Kommentar', href: '#', current: this.isCommentTab},
                {name: 'Verlinkung', href: '#', current: this.isLinkTab},
            ]
        },
    },

    methods: {
        selectMoneySource(moneySource){
            this.selectedMoneySource = moneySource;
            this.moneySource_query = '';
        },
        updateMoneySourceLink() {
            if (this.isLinked && this.selectedSumDetail.sum_money_source === null) {
                this.$inertia.post(route('project.sum.money.source.store'), {
                    sourceable_id: this.selectedSumDetail.id,
                    sourceable_type: this.selectedSumDetail.class,
                    linked_type: this.linkedType.type,
                    money_source_id: this.selectedMoneySource.id
                }, {
                    preserveScroll: true
                });
            } else if (this.isLinked && this.selectedSumDetail.sum_money_source) {
                this.$inertia.patch(route('project.sum.money.source.update', { sumMoneySource: this.selectedSumDetail.sum_money_source.id }), {
                    linked_type: this.linkedType.type,
                    money_source_id: this.selectedMoneySource.id
                }, {
                    preserveScroll: true
                });
            }
            else {
                this.$inertia.delete(route('project.sum.money.source.destroy',  { sumMoneySource: this.selectedSumDetail.sum_money_source.id }), {
                    preserveState: true,
                    preserveScroll: true
                });
            }

            this.closeModal(true);

        },
        formatDate(date) {
            const dateFormate = new Date(date);
            return dateFormate.toLocaleString('de-de', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        changeTab(selectedTab) {
            this.isCommentTab = false;
            this.isLinkTab = false;
            if (selectedTab.name === 'Kommentar') {
                this.isCommentTab = true;
            } else {
                this.isLinkTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        deleteCommentFromCell(comment) {

            this.$inertia.delete(route('sum.comments.delete', {comment: comment.id}), {
                preserveState: true,
                preserveScroll: true
            });
        },
        addCommentToCell() {
            if(!this.commentForm.comment){
                return;
            }
            this.commentForm.post(route('sum.comments.store'), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.commentForm.reset();
                }
            });
        },
    },
}
</script>
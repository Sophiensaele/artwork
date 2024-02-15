<template>
    <jet-dialog-modal :show="this.show" @close="this.close(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_user_invite.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <XIcon @click="this.close(false)"
                       class="h-5 w-5 flex text-secondary cursor-pointer absolute right-0 mr-10"
                       aria-hidden="true"/>
                <div class="mt-8 flex flex-col">
                    <span class="xsLight">
                        Schicht {{ this.getCurrentShiftCount() }}/{{ this.getMaxShiftCount() }}
                    </span>
                    <span class="headline1 -mt-2">
                        Qualifikationszuweisung
                    </span>
                </div>
                <div class="mt-3 xsLight">
                    In welcher Qualifikation soll
                    <img class="inline h-5 w-5 object-cover rounded-full"
                         :src="this.user.profile_photo_url"
                         :alt="'Profilfoto ' + this.user.display_name"
                    />
                    {{ this.user.display_name }} in folgender Schicht eingesetzt werden?
                </div>
                <div class="xsLight my-3 flex flex-col">
                    <span>
                        Schicht:
                        {{ this.currentShiftToAssign.shift.craft.name }}
                        ({{ this.currentShiftToAssign.shift.craft.abbreviation }})
                        &vert;
                        {{ this.currentShiftToAssign.shift.start }}
                        -
                        {{ this.currentShiftToAssign.shift.end }}
                    </span>
                </div>
                <div class="flex flex-col">
                    <div class="grid grid-cols-2 w-full gap-4">
                        <input v-for="availableShiftQualificationSlot in this.currentShiftToAssign.availableSlots"
                               type="button"
                               :value="'Als ' + availableShiftQualificationSlot.name + ' einsetzen'"
                               class="cursor-pointer bg-buttonBlue text-sm flex py-2 px-12 items-center border border-transparent rounded-full shadow-sm text-white focus:outline-none hover:bg-buttonHover"
                               @click="this.handleShift(this.currentShiftToAssign.shift.id, availableShiftQualificationSlot.id)"
                        />
                    </div>
                    <div class="w-full mt-4">
                        <input type="button"
                               value="Zuweisung überspringen"
                               class="w-full cursor-pointer bg-gray-600 text-sm flex py-2 px-12 items-center border border-transparent rounded-full shadow-sm text-white focus:outline-none hover:bg-gray-500"
                               @click="this.skipShift"
                        />
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import {defineComponent} from "vue";
import {XIcon} from "@heroicons/vue/outline";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

export default defineComponent({
    name: 'ShiftsQualificationsAssignmentModal',
    components: {
        UserPopoverTooltip,
        AddButton,
        XIcon,
        JetDialogModal
    },
    props: [
        'show',
        'user',
        'shifts'
    ],
    emits: [
        'close'
    ],
    data () {
        return {
            currentShiftToAssignIndex: 0,
            shiftsToAssign: []
        }
    },
    computed: {
        currentShiftToAssign() {
            return this.shifts[this.currentShiftToAssignIndex];
        }
    },
    methods: {
        getMaxShiftCount() {
            return this.shifts.length;
        },
        getCurrentShiftCount() {
            return (this.currentShiftToAssignIndex + 1);
        },
        isLastShiftToAssign() {
            return this.getCurrentShiftCount() === this.getMaxShiftCount();
        },
        nextShift() {
            this.currentShiftToAssignIndex++;
        },
        handleShift(shiftId, shiftQualificationId) {
            this.shiftsToAssign.push({
                shiftId: shiftId,
                shiftQualificationId: shiftQualificationId
            });

            if (this.isLastShiftToAssign()) {
                this.close(true);
                return;
            }

            this.nextShift();
        },
        skipShift() {
            if (this.isLastShiftToAssign()) {
                this.close(true);
                return;
            }

            this.nextShift();
        },
        close(closedForAssignment) {
            this.$emit('close', closedForAssignment, this.shiftsToAssign);
        }
    }
});
</script>
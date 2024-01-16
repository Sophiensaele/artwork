<template>
    <jet-dialog-modal :show="show" @close="this.close">
        <template #content>
            <img src="/Svgs/Overlays/illu_user_invite.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <XIcon @click="this.close"
                       class="h-5 w-5 flex text-secondary cursor-pointer absolute right-0 mr-10"
                       aria-hidden="true"/>
                <div class="mt-8 headline1">
                    {{this.mode === 'create' ? 'Rechte-Preset erstellen' : 'Rechte-Preset bearbeiten'}}
                </div>
                <div class="xsLight my-6">
                    {{
                        this.mode === 'create' ?
                            'Hier kannst du ein Rechte-Preset erstellen. Dieses kann bei der Nutzererstellung verwendet werden.' :
                            'Hier kannst du das Rechte-Preset "' + this.permission_preset.name + '" bearbeiten.'
                    }}
                </div>
                <div class="flex items-center">
                    <div class="relative w-full mr-4">
                        <input id="name"
                               v-model="this.permissionPresetForm.name"
                               type="text"
                               :class="[
                                   $page.props.errors.name ?
                                   'border-red-600 focus:border-red-600' :
                                   'border-gray-300 focus:border-primary',
                                   'mb-3 peer pl-0 h-12 w-full focus:border-t-transparent focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 text-primary placeholder-secondary placeholder-transparent'
                               ]"
                               placeholder="placeholder"
                        />
                        <label for="name"
                               :class="[
                                   $page.props.errors.name ?
                                   'errorText' :
                                   'text-secondary',
                                   'absolute left-0 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-base peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm'
                               ]"
                        >
                            Rechte-Preset Name
                        </label>
                        <span v-if="$page.props.errors.name" class="errorText">
                            {{$page.props.errors.name.charAt(0).toUpperCase() + $page.props.errors.name.slice(1)}}
                        </span>
                    </div>
                </div>
                <div v-for="(permissions, group) in available_permissions">
                    <h3 class="headline6Light mb-2 mt-3">{{ group }}</h3>
                    <div class="w-full flex items-center"
                         v-for="(permission) in permissions" :key=permission.id>
                        <div class="w-full flex justify-between items-center my-1">
                            <div class="flex items-center justify-start">
                                <input :key="permission.name"
                                       :value="permission.id"
                                       v-model="this.permissionPresetForm.permissions"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[this.permissionPresetForm.permissions.includes(permission.id) ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto text-sm" v-if="!permission.name_de">{{ permission.name }}</p>
                                <p :class="[this.permissionPresetForm.permissions.includes(permission.id) ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto text-sm" v-else>{{permission.name_de}}</p>
                            </div>
                            <div v-if="permission.showIcon !== false">
                                <TextToolTip :id="permission.id" :height="6" :width="6" :tooltip-text="permission.tooltipText" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full items-center text-center">
                    <AddButton :class="[
                            this.permissionPresetForm.permissions.length === 0 || this.permissionPresetForm.name === '' ?
                                'bg-secondary':
                                'bg-buttonBlue hover:bg-buttonHover focus:outline-none',
                                'mt-8 inline-flex items-center px-20 py-3 border border-transparent text-base font-bold uppercase shadow-sm text-secondaryHover'
                        ]"
                               @click="save"
                               :disabled="this.permissionPresetForm.permissions.length === 0 || this.permissionPresetForm.name === ''"
                               :text="this.mode === 'create' ? 'Erstellen' : 'Speichern'"
                               mode="modal"
                    />
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
import {useForm} from "@inertiajs/inertia-vue3";
import TextToolTip from "@/Layouts/Components/TextToolTip.vue";
import Label from "@/Jetstream/Label.vue";

export default defineComponent({
    components: {
        Label,
        TextToolTip,
        AddButton,
        JetDialogModal,
        XIcon,
    },
    props: [
        'show',
        'available_permissions',
        'mode',
        'permission_preset'
    ],
    data() {
        return {
            permissionPresetForm: useForm({
                name: this.permission_preset !== null ? this.permission_preset.name : '',
                permissions: this.permission_preset !== null ? this.permission_preset.permissions : []
            })
        }
    },
    methods: {
        save() {
            switch (this.mode) {
                case 'create':
                    this.permissionPresetForm.post(
                        route('permission-presets.store'),
                        {
                            onSuccess: this.close
                        }
                    );
                    break;
                case 'edit':
                    this.permissionPresetForm.patch(
                        route('permission-presets.update', {permission_preset: this.permission_preset.id}),
                        {
                            onSuccess: this.close
                        }
                    );
                    break;
            }
        },
        close() {
            this.permissionPresetForm.reset();

            //reset errors otherwise they will be shown if the modal is opened again
            if (this.$page.props.errors.name) {
                delete this.$page.props.errors.name;
            }

            this.$emit('close');
        }
    }
})
</script>
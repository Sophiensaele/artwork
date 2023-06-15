<template>
    <div class="w-full">
        <div>
            <div class="mb-6 -ml-12">
                <UserShiftPlanFunctionBar @previousTimeRange="previousTimeRange"
                                          @next-time-range="nextTimeRange"
                                          :dateValue="dateValue"></UserShiftPlanFunctionBar>
            </div>
            <div class="overflow-x-auto flex">
                <div class="flex flex-wrap w-full">
                    <div class="w-1/7 h-48 mx-1" :style="{minWidth: 200 + 'px'}"
                         v-for="day in daysWithVacationAndEvents">
                        <div class="calendarRoomHeader">
                            {{ day.day_string }} {{ day.day }}
                            <span class="text-shiftText subpixel-antialiased">
                                (<span
                                :class="day.shiftDurationWarning? 'text-error': ''">{{ calculateShiftDuration(day.events) }}</span>  |
                            <span :class="day.breakWarning">{{calculateTotalBreakDuration(day.events) }}</span>)</span>
                        </div>
                        <div v-if="day.in_vacation">
                            <div class="bg-shiftText text-sm p-1 flex items-center text-secondaryHover h-10">
                                nicht verfügbar
                            </div>
                        </div>
                        <div v-for="event in day.events" class="flex">
                            <SingleShiftPlanEvent :eventType="this.findEventTypeById(event.event_type_id)"
                                                  :project="this.findProjectById(event.projectId)"
                                                  :room="this.findRoomById(event.room_id)" :event="event"
                                                  :showRoom="true" :show-duration-info="true"></SingleShiftPlanEvent>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue'
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import UserVacations from "@/Pages/Users/Components/UserVacations.vue";
import TemporarilyHired from "@/Pages/Users/Components/TemporarilyHired.vue";
import Permissions from "@/mixins/Permissions.vue";
import dayjs from "dayjs";
import UserShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlanFunctionBar.vue";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import {Inertia} from "@inertiajs/inertia";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";

export default {
    name: "UserShiftPlan",
    mixins: [Permissions],
    components: {
        SingleShiftPlanEvent,
        ShiftPlanFunctionBar,
        UserShiftPlanFunctionBar
    },
    computed: {
        daysWithVacationAndEvents() {
            const daysArray = Object.values(this.daysWithEvents);
            return daysArray.map((day) => {
                const dayDate = new Date(day.full_day.split(".").reverse().join("-"));
                const inVacation = this.vacations.some((vacation) => {
                    const vacationFrom = new Date(vacation.from);
                    const vacationUntil = new Date(vacation.until);
                    return dayDate >= vacationFrom && dayDate <= vacationUntil;
                });

                const shiftDuration = this.calculateShiftDuration(day.events);
                const totalBreakDuration = this.calculateTotalBreakDuration(day.events);
                const shiftDurationWarning = shiftDuration > '10:00';
                const breakWarning = shiftDuration > '04:00' && totalBreakDuration < '00:30';

                return {
                    ...day,
                    in_vacation: inVacation,
                    shiftDuration,
                    totalBreakDuration,
                    shiftDurationWarning,
                    breakWarning,
                };
            });
        },
    },
    props: ['daysWithEvents', 'dateValue', 'projects', 'eventTypes', 'rooms', 'vacations'],
    methods: {
        previousTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[1] = this.getPreviousDay(this.dateValue[0]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() - dayDifference);
            this.dateValue[0] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        nextTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[0] = this.getNextDay(this.dateValue[1]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() + dayDifference + 1);
            this.dateValue[1] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
        },
        getNextDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getPreviousDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() - 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        findEventTypeById(eventTypeId) {
            return this.eventTypes.find(eventType => eventType.id === eventTypeId);
        },
        findRoomById(roomId) {
            return this.rooms.find(room => room.id === roomId);
        },
        updateTimes() {
            Inertia.reload({
                data: {
                    startDate: this.dateValue[0],
                    endDate: this.dateValue[1],
                }
            })
        },
        calculateShiftDuration(events) {
            const allShifts = events.flatMap(event => event.shifts);
            if (allShifts.length === 0) {
                return '00:00';
            }
            const startTimes = allShifts.map(shift => shift.start);
            const endTimes = allShifts.map(shift => shift.end);

            const earliestStartTime = Math.min(...startTimes.map(time => new Date(`2000-01-01 ${time}`)));
            const latestEndTime = Math.max(...endTimes.map(time => new Date(`2000-01-01 ${time}`)));

            console.log(earliestStartTime, latestEndTime)

            const durationInMinutes = (latestEndTime - earliestStartTime) / 60000;

            const hours = Math.floor(durationInMinutes / 60);
            const minutes = durationInMinutes % 60;

            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        },
        calculateTotalBreakDuration(events) {
            const allShifts = events.flatMap(event => event.shifts);
            if (allShifts.length === 0) {
                return '00:00';
            }
            const breakMinutes = allShifts.map(shift => shift.break_minutes);
            const totalBreakMinutes = breakMinutes.reduce((accumulator, currentValue) => accumulator + currentValue, 0);

            const hours = Math.floor(totalBreakMinutes / 60);
            const minutes = totalBreakMinutes % 60;

            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        },
    }
}
</script>

<style scoped>

</style>
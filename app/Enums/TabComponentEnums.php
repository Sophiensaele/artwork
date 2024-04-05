<?php

namespace App\Enums;

enum TabComponentEnums: string
{
    // custom tab component types
    case CHECKBOX = 'Checkbox';
    case TEXT_FIELD = 'TextField';
    case DROPDOWN = 'DropDown';
    case TEXT_AREA = 'TextArea';
    case TITLE = 'Title';


    // default tab component types

    case PROJECT_STATUS = 'ProjectStateComponent';
    case PROJECT_GROUP = 'ProjectGroupComponent';
    case PROJECT_TEAM = 'ProjectTeamComponent';
    case PROJECT_ATTRIBUTES = 'ProjectAttributesComponent';
    case CALENDAR = 'CalendarTab';
    case CHECKLIST = 'ChecklistComponent';
    case SHIFT_TAB = 'ShiftTab';
    case RELEVANT_DATES_FOR_SHIFT_PLANNING = 'RelevantDatesForShiftPlanningComponent';
    case SHIFT_CONTACT_PERSONS = 'ShiftContactPersonsComponent';
    case GENERAL_SHIFT_INFORMATION = 'GeneralShiftInformationComponent';
    case BUDGET = 'BudgetTab';
    case PROJECT_BUDGET_DEADLINE = 'ProjectBudgetDeadlineComponent';
    case COMMENT_TAB = 'CommentTab';
    case PROJECT_DOCUMENTS = 'ProjectDocumentsComponent';
    case PROJECT_TITLE = 'ProjectTitleComponent';
    case SEPARATOR = 'SeparatorComponent';

    /**
     * Get all available values
     * @return array[]
     */
    public static function getValues(): array
    {
        return [
            self::CHECKBOX->value => [
                'name' => self::CHECKBOX->value,
                'availableFields' => [
                    'label' => '',
                    'checked' => '',
                ]
            ],
            self::TEXT_FIELD->value => [
                'name' => self::TEXT_FIELD->value,
                'availableFields' => [
                    'label' => '',
                    'text' => '',
                    'placeholder' => '',
                ]
            ],
            self::DROPDOWN->value => [
                'name' => self::DROPDOWN->value,
                'availableFields' => [
                    'label' => '',
                    'options' => [
                        [
                            'value' => '',
                        ]
                    ],
                    'selected' => '',
                ]
            ],
            self::TEXT_AREA->value => [
                'name' => self::TEXT_AREA->value,
                'availableFields' => [
                    'label' => '',
                    'text' => '',
                    'placeholder' => '',
                ]
            ],
            self::TITLE->value => [
                'name' => self::TITLE->value,
                'availableFields' => [
                    'title' => '',
                ]
            ],
            self::SEPARATOR->value => [
                'name' => self::SEPARATOR->value,
                'availableFields' => [
                    'height' => 0,
                    'showLine' => false,
                ]
            ],
        ];
    }
}

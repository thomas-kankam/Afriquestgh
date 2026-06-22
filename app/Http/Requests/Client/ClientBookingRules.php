<?php

namespace App\Http\Requests\Client;

class ClientBookingRules
{
    public static function store(string $bookingType): array
    {
        return array_merge(self::commonRules(required: true), self::typeRules($bookingType, required: true), [
            'tourSlug' => 'required|string|exists:tours,tour_slug',
            'selectedDate' => 'required|date',
            'paymentMode' => 'required|in:online,onsite',
            'amount' => 'required|numeric|min:0',
            'clientSlug' => 'nullable|string|exists:clients,client_slug',
        ]);
    }

    public static function update(string $bookingType): array
    {
        return array_merge(self::commonRules(required: false), self::typeRules($bookingType, required: false), [
            'selectedDate' => 'sometimes|date',
            'selected_date' => 'sometimes|date',
            'paymentMode' => 'sometimes|in:online,onsite',
            'amount' => 'required_if:paymentMode,online|nullable|numeric|min:0',
            'special_requests' => 'nullable|string',
            'dietary_needs' => 'nullable|string',
            'additional_travelers' => 'nullable|array',
            'specialRequests' => 'nullable|string',
            'dietaryNeeds' => 'nullable|string',
        ]);
    }

    protected static function commonRules(bool $required): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return [
            'bookingType' => ($required ? 'required' : 'sometimes') . '|in:group,individual',
            'leadTraveler' => $rule . '|array|min:1',
            'leadTraveler.firstName' => $rule . '|string|max:255',
            'leadTraveler.lastName' => $rule . '|string|max:255',
            'leadTraveler.email' => $rule . '|email',
            'leadTraveler.phone' => 'nullable|string|max:50',
            'leadTraveler.nationality' => 'nullable|string|max:255',
            'specialRequests' => 'nullable|string',
            'dietaryNeeds' => 'nullable|string',
        ];
    }

    protected static function typeRules(string $bookingType, bool $required): array
    {
        if ($bookingType === 'individual') {
            return [
                'travelers' => ($required ? 'required' : 'sometimes') . '|integer|in:1',
                'groupDetails' => 'prohibited',
                'group_details' => 'prohibited',
                'additionalTravelers' => 'prohibited',
                'additional_travelers' => 'prohibited',
            ];
        }

        $groupRule = $required ? 'required' : 'sometimes';

        return [
            'travelers' => ($required ? 'required' : 'sometimes') . '|integer|min:2',
            'groupDetails' => $groupRule . '|array',
            'groupDetails.groupName' => $groupRule . '|string|max:255',
            'groupDetails.groupType' => $groupRule . '|string|max:255',
            'groupDetails.organization' => 'nullable|string|max:255',
            'additionalTravelers' => 'nullable|array',
            'additionalTravelers.*.firstName' => 'nullable|string|max:255',
            'additionalTravelers.*.lastName' => 'nullable|string|max:255',
            'additionalTravelers.*.email' => 'nullable|email',
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\BookingGuest;
use App\Models\BookingInquiry;
use App\Models\RoomType;
use App\Support\CompatResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends CrudController
{
    public function __construct()
    {
        parent::__construct(BookingInquiry::class);
    }

    public function index(Request $request): JsonResponse
    {
        $query = BookingInquiry::query()->with(['hotel', 'roomType', 'guests']);
        $user = $request->user();

        if (! $user) {
            $query->whereRaw('1 = 0');
        } elseif ($user->isStaffRole() && ! $user->isAdmin()) {
            $query->whereHas('hotel.assignedStaff', fn ($q) => $q->whereKey($user->id));
        } elseif (! $user->isAdmin()) {
            $query->where('customer_id', $user->id);
        }

        $this->applyFilters($request, $query);

        return response()->json(CompatResponse::page($query->latest('id')->paginate(20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email'],
            'guest_phone' => ['required', 'string', 'max:50'],
            'property' => ['required', 'exists:hotels,id'],
            'room_type' => ['required', 'exists:room_types,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'total_guests' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:new,inquiry,confirmed,pending'],
        ]);

        $roomType = RoomType::query()->find($data['room_type']);
        if (! $roomType || (int) $roomType->hotel_id !== (int) $data['property']) {
            return response()->json(['detail' => 'The specified room type does not belong to the specified property.', 'code' => 'invalid_room_type_for_property'], 422);
        }

        $bookingData = [
            'customer_name' => $data['guest_name'],
            'phone' => $data['guest_phone'],
            'email' => $data['guest_email'] ?? '',
            'customer_id' => $request->user()?->id,
            'hotel_id' => $data['property'],
            'room_type_id' => $data['room_type'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'infants' => 0,
            'status' => $data['status'] ?? 'new',
        ];

        $nights = max(1, now()->parse($bookingData['check_in'])->diffInDays(now()->parse($bookingData['check_out'])));
        $bookingData['estimated_total'] = (float) ($roomType->base_price ?: 0) * $nights;

        $booking = DB::transaction(fn () => BookingInquiry::query()->create($bookingData));

        return response()->json(CompatResponse::booking($booking->fresh(['hotel', 'roomType'])), 201);
    }

    public function inquiry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'property' => ['required', 'exists:hotels,id'],
            'room_type' => ['required', 'exists:room_types,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'infants' => ['nullable', 'integer', 'min:0'],
            'extra_bed_needed' => ['nullable', 'boolean'],
            'extra_bed_count' => ['nullable', 'integer', 'min:0'],
            'guests' => ['nullable', 'array'],
        ]);

        $roomType = RoomType::query()->find($data['room_type']);
        if (! $roomType || (int) $roomType->hotel_id !== (int) $data['property']) {
            return response()->json(['detail' => 'The specified room type does not belong to the specified property.', 'code' => 'invalid_room_type_for_property'], 422);
        }
        $nights = max(1, now()->parse($data['check_in'])->diffInDays(now()->parse($data['check_out'])));
        $estimatedTotal = (float) ($roomType->base_price ?: 0) * $nights;
        $estimatedTotal += (float) ($roomType->extra_bed_price ?: 0) * (int) ($data['extra_bed_count'] ?? 0) * $nights;

        $booking = DB::transaction(function () use ($data, $request, $estimatedTotal) {
            $booking = BookingInquiry::query()->create([
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? '',
                'customer_id' => $request->user()?->id,
                'hotel_id' => $data['property'],
                'room_type_id' => $data['room_type'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adults' => $data['adults'],
                'children' => $data['children'] ?? 0,
                'infants' => $data['infants'] ?? 0,
                'extra_bed_needed' => $data['extra_bed_needed'] ?? false,
                'extra_bed_count' => $data['extra_bed_count'] ?? 0,
                'estimated_total' => $estimatedTotal,
                'status' => 'new',
            ]);

            foreach ($data['guests'] ?? [] as $guest) {
                BookingGuest::query()->create(array_merge($guest, ['booking_inquiry_id' => $booking->id]));
            }

            return $booking;
        });

        return response()->json([
            'booking_id' => $booking->id,
            'customer_name' => $booking->customer_name,
            'phone' => $booking->phone,
            'email' => $booking->email,
            'property_id' => $booking->hotel_id,
            'room_type_id' => $booking->room_type_id,
            'check_in' => $booking->check_in->format('Y-m-d'),
            'check_out' => $booking->check_out->format('Y-m-d'),
            'adults' => $booking->adults,
            'children' => $booking->children,
            'estimated_total' => number_format((float) $booking->estimated_total, 2, '.', ''),
            'status' => $booking->status,
        ], 201);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate(['booking_id' => ['required', 'exists:booking_inquiries,id']]);
        $booking = BookingInquiry::query()->findOrFail($data['booking_id']);
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->isStaffRole())) {
            return response()->json(['detail' => 'You do not have permission to confirm bookings.'], 403);
        }

        if ($user->isStaffRole() && ! $user->isAdmin()) {
            $isAssigned = $booking->hotel->assignedStaff()->whereKey($user->id)->exists();
            if (! $isAssigned) {
                return response()->json(['detail' => 'You do not have permission to confirm this booking.'], 403);
            }
        }

        $booking->forceFill(['status' => 'confirmed'])->save();

        return response()->json([
            'booking_id' => $booking->id,
            'status' => $booking->status,
            'confirmed_at' => $booking->updated_at->toJSON(),
            'estimated_total' => (string) $booking->estimated_total,
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $booking = BookingInquiry::query()->findOrFail($id);
        $user = request()->user();

        if (! $user) {
            return response()->json(['detail' => 'Authentication required.'], 401);
        }

        if (! $user->isAdmin()) {
            $isOwner = (int) $booking->customer_id === (int) $user->id;
            $isAssignedStaff = $user->isStaffRole() && $booking->hotel->assignedStaff()->whereKey($user->id)->exists();
            if (! $isOwner && ! $isAssignedStaff) {
                return response()->json(['detail' => 'You do not have permission to cancel this booking.'], 403);
            }
        }

        $booking->forceFill(['status' => 'cancelled'])->save();

        return response()->json(CompatResponse::booking($booking->fresh(['hotel', 'roomType', 'guests'])));
    }
}

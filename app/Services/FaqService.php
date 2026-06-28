<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FaqService
{
    public static function syncFaqs(Model $parent, mixed $faqs): void
    {
        if ($faqs === null) {
            return;
        }

        if (! is_array($faqs)) {
            return;
        }

        DB::transaction(function () use ($parent, $faqs) {
            $existingIds = $parent->faqs()->pluck('id')->all();
            $keptIds = [];

            foreach (array_values($faqs) as $index => $faqData) {
                if (! is_array($faqData)) {
                    continue;
                }

                $id = array_key_exists('id', $faqData) && $faqData['id'] !== null && $faqData['id'] !== '' ? (int) $faqData['id'] : null;
                $data = [
                    'question' => (string) ($faqData['question'] ?? ''),
                    'answer' => (string) ($faqData['answer'] ?? ''),
                    'sort_order' => array_key_exists('sort_order', $faqData) && $faqData['sort_order'] !== null ? (int) $faqData['sort_order'] : ($index + 1),
                    'is_active' => array_key_exists('is_active', $faqData) ? (bool) $faqData['is_active'] : true,
                ];

                if ($id && in_array($id, $existingIds, true)) {
                    $faq = Faq::query()->find($id);
                    if ($faq && $faq->faqable_type === $parent->getMorphClass() && (int) $faq->faqable_id === (int) $parent->getKey()) {
                        $faq->update($data);
                        $keptIds[] = $id;
                    }
                } else {
                    $created = $parent->faqs()->create($data);
                    $keptIds[] = $created->id;
                }
            }

            $toDelete = array_diff($existingIds, $keptIds);
            if (! empty($toDelete)) {
                Faq::query()->whereIn('id', $toDelete)->delete();
            }
        });
    }
}

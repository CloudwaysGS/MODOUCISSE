<?php

namespace App\Service;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class SortieValidatorService
{
    public function validate(array $data): array
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($data, $this->getValidationConstraints());

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }

        return $errors;
    }

    private function getValidationConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'clientId' => new Assert\Optional(),
            'produitId' => new Assert\Optional(),
            'detailId' => new Assert\Optional(),
            'qtSortie' => [
                new Assert\NotBlank(['message' => 'La quantité ne doit pas être vide.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Cette valeur doit être de type numérique.']),
                new Assert\GreaterThan(['value' => 0, 'message' => 'La quantité doit être supérieure à 0.']),
            ],
            'prixUnit' => [
                new Assert\NotBlank(['message' => 'Le prix ne doit pas être vide.']),
                new Assert\Type(['type' => 'numeric', 'message' => 'Le prix doit être une valeur numérique.']),
                new Assert\GreaterThan(['value' => 0, 'message' => 'Le prix doit être une valeur numérique.']),
            ],
        ]);
    }
}
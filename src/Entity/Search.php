<?php

namespace App\Entity;
    class Search
    {
        private $nom;

        /**
         * @return mixed
         */
        public function getNom()
        {
            return $this->nom;
        }

        /**
         * @param mixed $nom
         */
        public function setNom($nom): void
        {
            $this->nom = $nom;
        }
    }
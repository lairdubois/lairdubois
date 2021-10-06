<?php

namespace App\Model;

interface InspirableInterface {

	// ReboundCount /////

	public function incrementReboundCount($by = 1);

	public function getReboundCount();

	// Rebounds /////

	public function getRebounds();

	// InspirationCount /////

	public function getInspirationCount();

	// Inspirations /////

	public function addInspiration(InspirableInterface $inspiration);

	public function removeInspiration(InspirableInterface $inspiration);

	public function getInspirations();

}
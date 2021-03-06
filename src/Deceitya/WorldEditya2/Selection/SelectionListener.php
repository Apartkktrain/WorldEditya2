<?php

declare(strict_types=1);

namespace Deceitya\WorldEditya2\Selection;

use Deceitya\WorldEditya2\Main;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

/**
 * pos1とpos2を設定
 *
 * @author deceitya
 */
class SelectionListener implements Listener
{
    /** @var int */
    private $item;

    public function __construct()
    {
        $this->item = Main::getInstance()->getWEConfing()->getSelectionItemId();
    }

    public function setFirstPosition(PlayerInteractEvent $event)
    {
        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            return;
        }

        $player = $event->getPlayer();
        if ($player->getInventory()->getItemInHand()->getId() === $this->item && $player->hasPermission('worldeditya2.command.pos1')) {
            $event->setCancelled();

            $block = $event->getBlock()->floor();
            $player->getServer()->dispatchCommand($player, "/pos1 {$block->x} {$block->y} {$block->z}");
        }
    }

    public function setSecondPosition(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if ($player->getInventory()->getItemInHand()->getId() === $this->item && $player->hasPermission('worldeditya2.command.pos2')) {
            $event->setCancelled();

            $block = $event->getBlock()->floor();
            $player->getServer()->dispatchCommand($player, "/pos2 {$block->x} {$block->y} {$block->z}");
        }
    }
}

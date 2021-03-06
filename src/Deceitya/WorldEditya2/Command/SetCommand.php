<?php

declare(strict_types=1);

namespace Deceitya\WorldEditya2\Command;

use InvalidArgumentException;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Deceitya\WorldEditya2\Cache\CacheManager;
use Deceitya\WorldEditya2\Cache\WECache;
use Deceitya\WorldEditya2\Config\MessageContainer;
use Deceitya\WorldEditya2\Selection\Selection;
use Deceitya\WorldEditya2\Task\SetTask;
use pocketmine\block\BlockFactory;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

use function explode;

/**
 * //setコマンド
 *
 * @author deceitya
 */
class SetCommand extends BaseCommand
{
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument('block'));

        $this->setPermission('worldeditya2.command.set');
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!($sender instanceof Player)) {
            $sender->sendMessage(MessageContainer::get('command.error.run_as_player'));

            return;
        }

        $selection = Selection::getSelection($sender);
        if ($selection->canExecute()) {
            $blockData = explode(':', $args['block']);
            $start = $selection->getStartPosition();
            $end = $selection->getEndPosition();
            $chunks = [];
            $maxx = $end->x >> 4;
            $maxz = $end->z >> 4;
            for ($x = $start->x >> 4; $x <= $maxx; $x++) {
                for ($z = $start->z >> 4; $z <= $maxz; $z++) {
                    $chunk = $sender->level->getChunk($x, $z);
                    if ($chunk !== null) {
                        $chunks[] = $chunk;
                    } else {
                        $sender->sendMessage(MessageContainer::get('chunk.not_loaded'));

                        return;
                    }
                }
            }

            $block = null;
            try {
                $block = BlockFactory::get((int) $blockData[0], (int) (isset($blockData[1]) ? $blockData[1] : 0));
            } catch (InvalidArgumentException $e) {
                $sender->sendMessage(TextFormat::RED . $e->getMessage());

                return;
            }

            $task = new SetTask(
                $chunks,
                $start,
                $end,
                $block,
                function (SetTask $task) {
                    Server::getInstance()->broadcastMessage(MessageContainer::get('command.set.complete', (string) $task->getTaskId()));
                }
            );
            $sender->getServer()->getAsyncPool()->submitTask($task);

            CacheManager::getInstance()->add($sender->getName(), new WECache($chunks, $start, $end));

            $sender->getServer()->broadcastMessage(MessageContainer::get(
                'command.set.start',
                (string) $task->getTaskId(),
                $sender->getName(),
                (string) $selection->count()
            ));
        } else {
            $pos1 = $selection->getFirstPosition();
            $pos2 = $selection->getSecondPosition();
            $sender->sendMessage(MessageContainer::get(
                'pos.invalid',
                $pos1 === null ? 'None' : "{$pos1->x}, {$pos1->y}, {$pos1->z} / {$pos1->level->getFolderName()}",
                $pos2 === null ? 'None' : "{$pos2->x}, {$pos2->y}, {$pos2->z} / {$pos2->level->getFolderName()}"
            ));
        }
    }
}

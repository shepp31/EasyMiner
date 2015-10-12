<?php
namespace EasyMinerCenter\Model\EasyMiner\Entities;
use LeanMapper\Entity;
use Nette\Utils\Json;


/**
 * Class Task - entita zachycující jednu konkrétní dataminingovou úlohu
 * @package EasyMinerCenter\Model\EasyMiner\Entities
 * @property int|null $taskId=null
 * @property string $taskUuid = ''
 * @property string $type m:Enum(Miner::TYPE_*)
 * @property int $rulesInRuleClipboardCount = 0
 * @property int $rulesCount = 0
 * @property string $rulesOrder = 'default'
 * @property string $name = ''
 * @property Miner $miner m:hasOne
 * @property string $state m:Enum(self::STATE_*)
 * @property string $taskSettingsJson = ''
 * @property string|null $resultsUrl = ''
 * @property-read TaskState $taskState
 * @property-read Rule[] $rules m:belongsToMany
 */
class Task extends Entity{
  const STATE_NEW='new';
  const STATE_IN_PROGRESS='in_progress';
  const STATE_SOLVED_HEADS='solved_heads';
  const STATE_SOLVED='solved';
  const STATE_FAILED='failed';
  const STATE_INTERRUPTED='interrupted';


  /**
   * Funkce vracející základní data v podobě pole
   * @param bool $includeSettings = false - pokud true, je do pole vloženo kompletní zadání úlohy
   * @return array
   */
  public function getDataArr($includeSettings=false){
    $result=[
      'id'=>$this->taskId,
      'uuid'=>$this->taskUuid,
      'miner'=>$this->miner->minerId,
      'name'=>$this->name,
      'type'=>$this->type,
      'state'=>$this->state,
      'rulesCount'=>$this->rulesCount,
      'rulesOrder'=>$this->rulesOrder
    ];
    if ($includeSettings){
      $result['settings']=$this->getTaskSettings();
    }
    return $result;
  }

  /**
   * Funkce vracející pole s nastaveními této úlohy
   * @return array
   * @throws \Nette\Utils\JsonException
   */
  public function getTaskSettings() {
    return Json::decode($this->taskSettingsJson,Json::FORCE_ARRAY);
  }

  /**
   * Funkce pro přiřazení nastavení úlohy
   * @param array|string|object $settings
   * @throws \Nette\Utils\JsonException
   */
  public function setTaskSettings($settings) {
    if (!empty($settings) && is_array($settings) || is_object($settings)){
      $settings=Json::encode($settings);
    }
    $this->taskSettingsJson=$settings;
  }

  /**
   * @return TaskState
   */
  public function getTaskState(){
    return new TaskState($this->state,$this->rulesCount,$this->resultsUrl);
  }

  /**
   * Funkce vracející seznam měr zajímavosti, které jsou použity u dané úlohy
   * @return string[]
   */
  public function getInterestMeasures(){
    $result=array();
    try{
      $taskSettings=Json::decode($this->taskSettingsJson,Json::FORCE_ARRAY);
      $IMs=$taskSettings['rule0']['IMs'];
    }catch (\Exception $e){}
    if (!empty($IMs)){
      foreach ($IMs as $IM){
        $result[]=$IM['name'];
      }
    }
    return $result;
  }

  /**
   * @param string $rulesOrder
   */
  public function setRulesOrder($rulesOrder){
    $rulesOrder=strtolower($rulesOrder);
    $IMsArr=$this->getInterestMeasures();
    $supportedIM=false;
    foreach($IMsArr as $im){
      if (strtolower($im)==$rulesOrder){
        $supportedIM=true;
        break;
      }
    }
    if ($rulesOrder=='default'){
      $supportedIM=true;
    }
    if ($supportedIM){
      $this->row->rules_order=$rulesOrder;
    }else{
      throw new \InvalidArgumentException('Unsupported interest measure!');
    }
  }

  /**
   * @return string
   */
  public function getRulesOrder() {
    return $this->row->rules_order;
  }
} 
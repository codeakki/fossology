<?php
namespace sw360\thrift;

/**
 * Autogenerated by Thrift Compiler (0.9.1)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 *  @generated
 */
use Thrift\Base\TBase;
use Thrift\Type\TType;
use Thrift\Type\TMessageType;
use Thrift\Exception\TException;
use Thrift\Exception\TProtocolException;
use Thrift\Protocol\TProtocol;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Exception\TApplicationException;


final class RequestStatus {
  const SUCCESS = 0;
  const SENT_TO_MODERATOR = 1;
  const FAILURE = 2;
  const IN_USE = 3;
  static public $__names = array(
    0 => 'SUCCESS',
    1 => 'SENT_TO_MODERATOR',
    2 => 'FAILURE',
    3 => 'IN_USE',
  );
}

final class ModerationState {
  const PENDING = 0;
  const APPROVED = 1;
  const REJECTED = 2;
  const INPROGRESS = 3;
  static public $__names = array(
    0 => 'PENDING',
    1 => 'APPROVED',
    2 => 'REJECTED',
    3 => 'INPROGRESS',
  );
}

final class Visibility {
  const PRIVATE = 0;
  const ME_AND_MODERATORS = 1;
  const BUISNESSUNIT_AND_MODERATORS = 2;
  const EVERYONE = 3;
  static public $__names = array(
    0 => 'PRIVATE',
    1 => 'ME_AND_MODERATORS',
    2 => 'BUISNESSUNIT_AND_MODERATORS',
    3 => 'EVERYONE',
  );
}

class SW360Exception extends TException {
  static $_TSPEC;

  public $why = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'why',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['why'])) {
        $this->why = $vals['why'];
      }
    }
  }

  public function getName() {
    return 'SW360Exception';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->why);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('SW360Exception');
    if ($this->why !== null) {
      $xfer += $output->writeFieldBegin('why', TType::STRING, 1);
      $xfer += $output->writeString($this->why);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class DocumentState {
  static $_TSPEC;

  public $isOriginalDocument = null;
  public $moderationState = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'isOriginalDocument',
          'type' => TType::BOOL,
          ),
        2 => array(
          'var' => 'moderationState',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['isOriginalDocument'])) {
        $this->isOriginalDocument = $vals['isOriginalDocument'];
      }
      if (isset($vals['moderationState'])) {
        $this->moderationState = $vals['moderationState'];
      }
    }
  }

  public function getName() {
    return 'DocumentState';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->isOriginalDocument);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->moderationState);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('DocumentState');
    if ($this->isOriginalDocument !== null) {
      $xfer += $output->writeFieldBegin('isOriginalDocument', TType::BOOL, 1);
      $xfer += $output->writeBool($this->isOriginalDocument);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->moderationState !== null) {
      $xfer += $output->writeFieldBegin('moderationState', TType::I32, 2);
      $xfer += $output->writeI32($this->moderationState);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class RequestSummary {
  static $_TSPEC;

  public $requestStatus = null;
  public $totalAffectedElements = null;
  public $totalElements = null;
  public $message = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'requestStatus',
          'type' => TType::I32,
          ),
        2 => array(
          'var' => 'totalAffectedElements',
          'type' => TType::I32,
          ),
        3 => array(
          'var' => 'totalElements',
          'type' => TType::I32,
          ),
        4 => array(
          'var' => 'message',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['requestStatus'])) {
        $this->requestStatus = $vals['requestStatus'];
      }
      if (isset($vals['totalAffectedElements'])) {
        $this->totalAffectedElements = $vals['totalAffectedElements'];
      }
      if (isset($vals['totalElements'])) {
        $this->totalElements = $vals['totalElements'];
      }
      if (isset($vals['message'])) {
        $this->message = $vals['message'];
      }
    }
  }

  public function getName() {
    return 'RequestSummary';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->requestStatus);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->totalAffectedElements);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->totalElements);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->message);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('RequestSummary');
    if ($this->requestStatus !== null) {
      $xfer += $output->writeFieldBegin('requestStatus', TType::I32, 1);
      $xfer += $output->writeI32($this->requestStatus);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->totalAffectedElements !== null) {
      $xfer += $output->writeFieldBegin('totalAffectedElements', TType::I32, 2);
      $xfer += $output->writeI32($this->totalAffectedElements);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->totalElements !== null) {
      $xfer += $output->writeFieldBegin('totalElements', TType::I32, 3);
      $xfer += $output->writeI32($this->totalElements);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->message !== null) {
      $xfer += $output->writeFieldBegin('message', TType::STRING, 4);
      $xfer += $output->writeString($this->message);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}



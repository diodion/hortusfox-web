<?php

/**
 * Class InventoryController
 * 
 * Gateway to the inventory
 */
class InventoryController extends BaseController {
    const INDEX_LAYOUT = 'layout';

	/**
	 * Perform base initialization
	 * 
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct(self::INDEX_LAYOUT);

		if (!app('inventory_enable')) {
			throw new \Exception(403);
		}
	}

    /**
	 * Handles URL: /inventory
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_inventory($request)
	{
		$user = UserModel::getAuthUser();

		$inventory = InventoryModel::getInventory();

		$expand = $request->params()->query('expand', null);
		
		return parent::view(['content', 'inventory'], [
			'user' => $user,
			'inventory' => $inventory,
			'_expand_inventory_item' => $expand
		]);
	}

	/**
	 * Handles URL: /inventory/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_inventory_item($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'name' => 'required',
			'group' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$name = $request->params()->query('name', null);
		$group = $request->params()->query('group', null);
		$location = $request->params()->query('location', null);
		$description = $request->params()->query('description', null);
		$tags = $request->params()->query('tags', null);
		$amount = $request->params()->query('amount', null);
		$photo = $request->params()->query('photo', null);

		$id = InventoryModel::addItem($name, $description, $tags, $location, $amount, $group, $photo);

		return redirect('/inventory?expand=' . $id . '#anchor-item-' . $id);
	}

	/**
	 * Handles URL: /inventory/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_inventory_item($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'id' => 'required',
			'name' => 'required',
			'group' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$id = $request->params()->query('id', null);
		$name = $request->params()->query('name', null);
		$group = $request->params()->query('group', null);
		$location = $request->params()->query('location', null);
		$amount = $request->params()->query('amount', null);
		$description = $request->params()->query('description', null);
		$tags = $request->params()->query('tags', null);
		$photo = $request->params()->query('photo', null);

		InventoryModel::editItem($id, $name, $description, $tags, $location, $amount, $group, $photo);

		return redirect('/inventory?expand=' . $id . '#anchor-item-' . $id);
	}

	/**
	 * Handles URL: /inventory/amount/increment
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function inc_inventory_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			$amount = InventoryModel::incAmount($id);

			return json([
				'code' => 200,
				'amount' => $amount
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/amount/decrement
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function dec_inventory_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			$amount = InventoryModel::decAmount($id);

			return json([
				'code' => 200,
				'amount' => $amount
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function remove_inventory_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			InventoryModel::removeItem($id);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/qrcode
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function generate_qr_code($request)
	{
		try {
			$item = $request->params()->query('item', null);

			$qrcode = InventoryModel::generateQRCode($item);

			return json([
				'code' => 200,
				'qrcode' => $qrcode
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/qrcode/bulk
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function get_bulk_qr_codes($request)
	{
		try {
			$result = [];

			$invitems = json_decode($request->params()->query('list', null));
			foreach ($invitems as $invitem) {
				$code = InventoryModel::generateQRCode($invitem[0]);
				if ($code) {
					$result[] = [
						'invitemid' => $invitem[0],
						'invitemname' => $invitem[1],
						'invgroup' => $invitem[2],
						'qrcode' => $code
					];
				}
			}

			return json([
				'code' => 200,
				'list' => $result
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/export
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function export_items($request)
	{
		try {
			$result = [];

			$list = json_decode($request->params()->query('list', null));
			$format = $request->params()->query('format', null);
			
			$storage_file = InventoryModel::exportItems($list, $format);

			return json([
				'code' => 200,
				'resource' => asset('exports/' . $storage_file)
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/group/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function add_inventory_group_item($request)
	{
		try {
			$token = $request->params()->query('token', null);
			$label = $request->params()->query('label', null);

			$itemid = InvGroupModel::addItem($token, $label);

			return json([
				'code' => 200,
				'itemid' => $itemid
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/group/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function edit_inventory_group_item($request)
	{
		try {
			$id = $request->params()->query('id', null);
			$what = $request->params()->query('what', null);
			$value = $request->params()->query('value', null);

			InvGroupModel::editItem($id, $what, $value);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/group/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function remove_inventory_group_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			InvGroupModel::removeItem($id);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}
}

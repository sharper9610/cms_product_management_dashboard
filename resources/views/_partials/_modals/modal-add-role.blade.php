<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-simple modal-dialog-centered modal-add-new-role">
    <div class="modal-content">
      <div class="modal-body p-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <h4 class="role-title mb-2 pb-0">Add New Role</h4>
          <p>Set role permissions</p>
        </div>
        <!-- Add role form -->
        <form id="roleForm" class="row g-3" onsubmit="return false">
          @csrf
          <input type="hidden" value="" name="roleId" id="roleId">

          <div class="col-12 form-control-validation mb-3">
            <div class="form-floating form-floating-outline">
              <input type="text" id="roleName" name="roleName" class="form-control" placeholder="Enter a role name" tabindex="-1" />
              <label for="roleName">Role Name</label>
            </div>
          </div>
          <div class="col-12">
            <h5>Role Permissions</h5>
            <!-- Permission table -->
            <div >
              <table class="table table-flush-spacing">
                <tbody>
                  <tr>
                    <td class="text-nowrap fw-medium">Administrator Access <i class="icon-base ri ri-information-line" data-bs-toggle="tooltip" data-bs-placement="top" title="Allows a full access to the system"></i></td>
                    <td>
                      <div class="d-flex justify-content-end">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="selectAll" />
                          <label class="form-check-label" for="selectAll"> Select All </label>
                        </div>
                      </div>
                    </td>
                  </tr>

                  @foreach($permissions as $group => $groupPermissions)
                    <tr>
                      <td class="text-nowrap fw-medium">{{ucfirst($group)}}</td>
                      <td>
                        <div class="d-flex justify-content-end">
                          @foreach($groupPermissions as $permission)
                          <div class="form-check me-4 me-lg-12">
                            <input class="form-check-input permission-field"
                                   type="checkbox"
                                   id="{{ $permission['name'] }}" name="permission[{{$permission['id']}}]" value="{{ $permission['id'] }}"
                            />
                            <label class="form-check-label" for="{{ $permission['name'] }}">
                              {{ ucwords(str_replace('.', ' ', str_replace($group . '.', '', $permission['name']))) }}
                            </label>
                          </div>

                          @endforeach
                        </div>
                      </td>
                    </tr>
                  @endforeach

                </tbody>
              </table>
            </div>
            <!-- Permission table -->
          </div>
          <div class="col-12 text-center">
            <button type="submit" class="btn btn-primary me-3">Submit</button>
            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
          </div>
        </form>
        <!--/ Add role form -->
      </div>
    </div>
  </div>
</div>
<!--/ Add Role Modal -->

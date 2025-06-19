<!-- Edit Modal -->
<div class="modal fade" id="editPatient" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
<div class="modal-dialog" style="width: 75%">
    <div class="modal-content">
        <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Edit Patient</h4>
        </div>
        <form action="{{ route('patient.update', $patient->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="modal-body">
        <div class="row">
            <div class=" col-md-4 form-group">
                <label>First Name:</label>
                <input type="text" name="first_name" value="{{ $patient->first_name }}" class="form-control" required>
            </div>
            <div class=" col-md-4 form-group">
                <label>Middle Name:</label>
                <input type="text" name="middle_name" value="{{ $patient->middle_name }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>Last Name:</label>
                <input type="text" name="last_name" value="{{ $patient->last_name }}" class="form-control" required>
            </div>
            <div class=" col-md-4 form-group">
                <label>Email:</label>
                <input type="email" name="email" value="{{ $patient->email }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>Phone:</label>
                <input type="number" name="phone" value="{{ $patient->phone }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>Gender:</label>
                <select name="gender" class="form-control" required>
                    <option value="male" {{ $patient->gender == 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ $patient->gender == 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ $patient->gender == 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class=" col-md-4 form-group">
                <label>Marital Status:</label>
                <select name="marital_status" class="form-control">
                    @if($patient->marital_status)
                            <option value="{{ $patient->marital_status }}">{{ $patient->marital_status }}</option>
                    @else
                            <option value=""></option>
                    @endif
                    <option value="married">Married</option>
                    <option value="single">Single</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class=" col-md-4 form-group">
                <label>Blood Group:</label>
                <select name="blood_group" class="form-control">
                    @if($patient->blood_group)
                            <option value="{{ $patient->blood_group }}">{{ $patient->blood_group }}</option>
                    @else
                            <option value="">Select</option>
                    @endif
                    <option>A+</option>
                    <option>A-</option>
                    <option>B+</option>
                    <option>B-</option>
                    <option>AB+</option>
                    <option>AB-</option>
                    <option>O+</option>
                    <option>O-</option>
                </select>
            </div>
            <div class=" col-md-4 form-group">
                <label>Date of Birth:</label>
                <input type="text" name="birth_date" value="{{ $patient->birth_date }}" class="form-control" id="nepaliDate5">
            </div>
            <div class=" col-md-4 form-group">
                <label>Age:</label>
                <input type="number" name="age" value="{{ $patient->age }}" class="form-control" required>
            </div>
            <div class=" col-md-4 form-group">
                <label>Relative Name:</label>
                <input type="text" name="relative_name" value="{{ $patient->relative_name }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>Relative Phone:</label>
                <input type="number" name="relative_phone" value="{{ $patient->relative_phone }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>Country:</label>
                <input type="text" name="country" value="{{ $patient->country }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>State:</label>
                <input type="text" name="state" value="{{ $patient->state }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>District:</label>
                <input type="text" name="district" value="{{ $patient->district }}" class="form-control">
            </div>
            <div class=" col-md-4 form-group">
                <label>Location:</label>
                <textarea name="location" class="form-control" required rows="3">{{ $patient->location }}</textarea>
            </div>
            <div class=" col-md-4 form-group">
                <label>Occupation:</label>
                <textarea name="occupation" class="form-control" rows="3">{{ $patient->occupation }}</textarea>
            </div>
            <div class=" col-md-4 form-group">
                <label>Description:</label>
                <textarea name="description" class="form-control" rows="3">{{ $patient->description }}</textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button data-dismiss="modal" class="btn btn-default" type="button">Close</button>
            <button class="btn btn-danger" type="reset">Reset</button>
            <button class="btn btn-success" type="submit">Save changes</button>
        </div>
        </form>

        @permission('patient.destroy')
            <form action="{{ route('patient.destroy', $patient->id) }}" method="POST" id="deleteConfirm" class="pull-left" style="display:inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Delete</button>
            </form>
        @endpermission
        </div>
    </div>
</div>
</div>

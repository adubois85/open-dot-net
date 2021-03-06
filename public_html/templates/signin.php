<!-- setting up the modal -->
<div class="modal fade" id="signin-modal" tabindex="-1" role="dialog" aria-labelledby="signin">

	<div class="modal-dialog " role="document">

		<div class="modal-content">

			<div class="modal-header">
				<!-- button to dismiss the modal -->
				<button type="button" class="close" data-dismiss="modal" aria-label="close"><span aria-hidden="true"> &times; </span>
				</button>

				<!-- header for the modal -->
				<h4 class="modal-title">Please Sing In</h4>
			</div>


			<div class="modal-body">
				<!-- actual login form -->
				<form #signInForm="ngForm" class="form-horizontal" name="signInForm" id="signinForm" novalidate
						(ngSubmit)="signIn();">

					<!-- username goes here -->
					<div class="form-group">
						<label for="signin-email" class="col-sm-2 control-label">Email</label>
						<div class="col-sm-10">
							<input type="email" name="signin-email" id="signin-email" required [(ngModel)]=signin.profileEmail
									 #profileEmail="ngModel" class="form-control input-sm">
						</div>
					</div>

					<!-- password goes here -->
					<div class="form-group">
						<label for="signin-password" class=" col-sm-2 control-label">Password</label>
						<div class="col-sm-10">
							<input type="password" id="signin-password" name="signin-password" required
									 [(ngModel)]="signin.profilePassword" #profilePassword="ngModel"
									 class="form-control input-sm">
						</div>
					</div>


					<button type="submit" id="submit" [disabled]="signInForm.invalid" class="btn btn-info">submit
					</button>
				</form>
				<div *ngIf="status !== null" class="alert alert-dismissible" [ngClass]="status.type" role="alert">
					<button type="button" class="close" aria-label="Close" (click)="status = null;"><span
							aria-hidden="true">&times;</span></button>
					{{ status.message }}


				</div>


			</div>
		</div>


	</div>
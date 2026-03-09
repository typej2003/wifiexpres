<x-admin-layout>
  <div>
      <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Escritorio X</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="/">Inicio</a></li>
            <li class="breadcrumb-item active">Escritorio</li>
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">
    <div class="row">
        @if(auth()->user()->role=='admin')
        @endif
        @if(auth()->user()->role=='aliado')
            <livewire:admin.dashboard.crear-tickets-dashboard />    
            <livewire:admin.dashboard.tickets-vendidos-dashboard />
        @endif
      </div>
    </div><!-- /.container-fluid -->
    <div class="row">
        @if(auth()->user()->role=='admin')
        
        @endif
        @if(auth()->user()->role=='aliado')
            <livewire:admin.dashboard.users-mikrotik-count />
            <livewire:admin.dashboard.tickets-count />
            <livewire:admin.dashboard.ventas-tickets-count />
          
        @endif
      </div>
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
  </div>
</x-admin-layout>

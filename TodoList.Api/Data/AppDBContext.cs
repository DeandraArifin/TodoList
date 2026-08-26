using Microsoft.EntityFrameworkCore;
using TodoList.Api.Models;

namespace TodoList.Api.Data
{
    public class AppDBContext : DbContext
    {
        public AppDBContext(DbContextOptions<AppDBContext> options) : base(options)
        {
        }

        public DbSet<TodoItem> TodoItems { get; set; }
        public DbSet<TodoListItem> TodoListItems { get; set; }

        protected override void OnModelCreating(ModelBuilder modelBuilder)
        {
            modelBuilder.Entity<TodoListItem>(entity =>
            {
                entity.HasKey(list => list.Id);
                entity.Property(list => list.Title).IsRequired();
                entity.HasMany(list => list.Todos)
                    .WithOne(item => item.TodoList)
                    .HasForeignKey(item => item.TodoListItemId)
                    .OnDelete(DeleteBehavior.Cascade);
            });

            modelBuilder.Entity<TodoItem>(entity =>
            {
                entity.HasKey(item => item.Id);
                entity.Property(item => item.Title).IsRequired();
            });
        }
    }
}

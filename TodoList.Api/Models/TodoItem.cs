namespace TodoList.Api.Models
{
    public class TodoItem
    {
        public Guid Id { get; set; }
        public string Title { get; set; } = string.Empty;
        public Boolean IsCompleted { get; set; } = false;
    }
}
